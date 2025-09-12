<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class AnkiImportService
{
    private MediaStorageService $mediaStorage;

    private string $tempDir;

    public function __construct(MediaStorageService $mediaStorage)
    {
        $this->mediaStorage = $mediaStorage;
        $this->tempDir = storage_path('app/temp/anki');
    }

    /**
     * Parse Anki .apkg file and return flashcard data
     */
    public function parseAnkiPackage(UploadedFile $file, int $unitId): array
    {
        try {
            // Validate file extension
            if (strtolower($file->getClientOriginalExtension()) !== 'apkg') {
                return [
                    'success' => false,
                    'error' => 'File must be an Anki package (.apkg) file',
                ];
            }

            // Create temporary directory
            $extractPath = $this->tempDir.'/'.Str::uuid();
            File::makeDirectory($extractPath, 0755, true);

            try {
                // Extract the .apkg file
                $zip = new ZipArchive;
                $result = $zip->open($file->getPathname());

                if ($result !== true) {
                    return [
                        'success' => false,
                        'error' => 'Failed to open Anki package file',
                    ];
                }

                $zip->extractTo($extractPath);
                $zip->close();

                // Parse the database and media
                $parseResult = $this->parseExtractedPackage($extractPath, $unitId);

                // Clean up temporary files
                File::deleteDirectory($extractPath);

                return $parseResult;

            } catch (\Exception $e) {
                // Clean up on error
                if (File::isDirectory($extractPath)) {
                    File::deleteDirectory($extractPath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Anki import error', [
                'file' => $file->getClientOriginalName(),
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse Anki package: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse extracted Anki package contents
     */
    private function parseExtractedPackage(string $extractPath, int $unitId): array
    {
        $databasePath = $extractPath.'/collection.anki2';
        $mediaMapPath = $extractPath.'/media';

        if (! File::exists($databasePath)) {
            return [
                'success' => false,
                'error' => 'Invalid Anki package: collection.anki2 not found',
            ];
        }

        // Parse media files if they exist
        $mediaFiles = [];
        $mediaMap = [];

        if (File::exists($mediaMapPath)) {
            $mediaMapContent = File::get($mediaMapPath);
            $mediaMap = json_decode($mediaMapContent, true) ?? [];

            // Extract media files
            $mediaResult = $this->extractMediaFiles($extractPath, $mediaMap, $unitId);
            if ($mediaResult['success']) {
                $mediaFiles = $mediaResult['media_files'];
            }
        }

        // Parse SQLite database
        $cardsResult = $this->parseDatabaseFile($databasePath, $mediaFiles);

        if (! $cardsResult['success']) {
            return $cardsResult;
        }

        return [
            'success' => true,
            'cards' => $cardsResult['cards'],
            'note_types' => $cardsResult['note_types'],
            'media_files' => $mediaFiles,
            'total_cards' => count($cardsResult['cards']),
            'media_count' => count($mediaFiles),
            'deck_info' => $cardsResult['deck_info'] ?? [],
        ];
    }

    /**
     * Parse Anki SQLite database file
     */
    private function parseDatabaseFile(string $databasePath, array $mediaFiles): array
    {
        try {
            // Open SQLite database
            $pdo = new \PDO('sqlite:'.$databasePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Get note types (models)
            $noteTypes = $this->extractNoteTypes($pdo);

            // Get decks information
            $deckInfo = $this->extractDeckInfo($pdo);

            // Extract cards and notes
            $cards = $this->extractCards($pdo, $noteTypes, $mediaFiles);

            $pdo = null; // Close connection

            return [
                'success' => true,
                'cards' => $cards,
                'note_types' => $noteTypes,
                'deck_info' => $deckInfo,
            ];

        } catch (\Exception $e) {
            Log::error('Anki database parsing error', [
                'database_path' => $databasePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse Anki database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Extract note types from Anki database
     */
    private function extractNoteTypes(\PDO $pdo): array
    {
        $stmt = $pdo->prepare('SELECT models FROM col LIMIT 1');
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result || ! $result['models']) {
            return [];
        }

        $modelsData = json_decode($result['models'], true);
        $noteTypes = [];

        foreach ($modelsData as $modelId => $model) {
            $fields = [];
            foreach ($model['flds'] as $field) {
                $fields[] = $field['name'];
            }

            $templates = [];
            foreach ($model['tmpls'] as $template) {
                $templates[] = [
                    'name' => $template['name'],
                    'qfmt' => $template['qfmt'], // Question format
                    'afmt' => $template['afmt'],  // Answer format
                ];
            }

            $noteTypes[$modelId] = [
                'name' => $model['name'],
                'type' => $model['type'], // 0 = Standard, 1 = Cloze
                'fields' => $fields,
                'templates' => $templates,
                'css' => $model['css'] ?? '',
            ];
        }

        return $noteTypes;
    }

    /**
     * Extract deck information from Anki database
     */
    private function extractDeckInfo(\PDO $pdo): array
    {
        $stmt = $pdo->prepare('SELECT decks FROM col LIMIT 1');
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result || ! $result['decks']) {
            return [];
        }

        $decksData = json_decode($result['decks'], true);
        $deckInfo = [];

        foreach ($decksData as $deckId => $deck) {
            $deckInfo[$deckId] = [
                'name' => $deck['name'],
                'description' => $deck['desc'] ?? '',
                'config' => $deck['conf'] ?? 1,
            ];
        }

        return $deckInfo;
    }

    /**
     * Extract cards and notes from Anki database
     */
    private function extractCards(\PDO $pdo, array $noteTypes, array $mediaFiles): array
    {
        $cards = [];

        // Query to get notes with their cards
        $stmt = $pdo->prepare('
            SELECT n.id as note_id, n.mid as model_id, n.flds as fields, n.tags,
                   c.id as card_id, c.ord as template_ord, c.type as card_type
            FROM notes n
            LEFT JOIN cards c ON n.id = c.nid
            WHERE c.id IS NOT NULL
            ORDER BY n.id, c.ord
        ');

        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $modelId = $row['model_id'];
            $templateOrd = $row['template_ord'];

            if (! isset($noteTypes[$modelId])) {
                continue; // Skip if note type not found
            }

            $noteType = $noteTypes[$modelId];
            $fields = explode("\x1F", $row['fields']); // Anki uses ASCII 0x1F as separator
            $tags = explode(' ', trim($row['tags']));

            // Get the template for this card
            $template = $noteType['templates'][$templateOrd] ?? $noteType['templates'][0];

            $card = $this->processAnkiCard($noteType, $template, $fields, $tags, $mediaFiles);

            if ($card) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * Process individual Anki card into our format
     */
    private function processAnkiCard(array $noteType, array $template, array $fields, array $tags, array $mediaFiles): ?array
    {
        try {
            // Map field names to values
            $fieldMap = [];
            foreach ($noteType['fields'] as $index => $fieldName) {
                $fieldMap[$fieldName] = $fields[$index] ?? '';
            }

            // Determine card type
            $cardType = $this->determineCardType($noteType, $template, $fieldMap);

            // Generate question and answer from templates
            $question = $this->processTemplate($template['qfmt'], $fieldMap, $mediaFiles);
            $answer = $this->processTemplate($template['afmt'], $fieldMap, $mediaFiles);

            // Clean HTML tags for basic question/answer
            $cleanQuestion = strip_tags($question);
            $cleanAnswer = strip_tags($answer);

            if (empty(trim($cleanQuestion)) || empty(trim($cleanAnswer))) {
                return null; // Skip empty cards
            }

            $card = [
                'card_type' => $cardType,
                'question' => $cleanQuestion,
                'answer' => $cleanAnswer,
                'hint' => null,
                'difficulty_level' => 'medium',
                'tags' => array_filter($tags),
                'import_source' => 'anki',
                'anki_data' => [
                    'note_type' => $noteType['name'],
                    'template' => $template['name'],
                    'original_question' => $question,
                    'original_answer' => $answer,
                    'fields' => $fieldMap,
                ],
            ];

            // Process based on card type
            switch ($cardType) {
                case 'cloze':
                    $card = $this->processAnkiCloze($card, $fieldMap);
                    break;

                case 'multiple_choice':
                    $card = $this->processAnkiMultipleChoice($card, $fieldMap);
                    break;

                case 'image_occlusion':
                    $card = $this->processAnkiImageOcclusion($card, $fieldMap, $mediaFiles);
                    break;
            }

            // Extract media references
            $card = $this->extractMediaReferences($card, $mediaFiles);

            return $card;

        } catch (\Exception $e) {
            Log::warning('Failed to process Anki card', [
                'note_type' => $noteType['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Determine card type from Anki note type
     */
    private function determineCardType(array $noteType, array $template, array $fieldMap): string
    {
        // Check for cloze note type
        if ($noteType['type'] == 1) {
            return 'cloze';
        }

        // Check for image occlusion pattern
        if (stripos($noteType['name'], 'image occlusion') !== false) {
            return 'image_occlusion';
        }

        // Check for multiple choice patterns
        foreach ($fieldMap as $fieldName => $fieldValue) {
            if (stripos($fieldName, 'choice') !== false ||
                stripos($fieldName, 'option') !== false) {
                return 'multiple_choice';
            }
        }

        // Check template content for cloze syntax
        $allContent = $template['qfmt'].' '.$template['afmt'];
        foreach ($fieldMap as $fieldValue) {
            $allContent .= ' '.$fieldValue;
        }

        if (preg_match('/\{\{c\d+::[^}]+\}\}/', $allContent)) {
            return 'cloze';
        }

        // Default to basic card
        return 'basic';
    }

    /**
     * Process Anki template with field substitution
     */
    private function processTemplate(string $template, array $fieldMap, array $mediaFiles): string
    {
        $processed = $template;

        // Replace field references {{Field}} with actual values
        foreach ($fieldMap as $fieldName => $fieldValue) {
            $patterns = [
                '/\{\{'.preg_quote($fieldName).'\}\}/',
                '/\{\{#'.preg_quote($fieldName).'\}\}.*?\{\{\/'.preg_quote($fieldName).'\}\}/s',
                '/\{\{\^'.preg_quote($fieldName).'\}\}.*?\{\{\/'.preg_quote($fieldName).'\}\}/s',
            ];

            foreach ($patterns as $pattern) {
                if ($fieldName && trim($fieldValue)) {
                    $processed = preg_replace($pattern, $fieldValue, $processed);
                } else {
                    $processed = preg_replace($pattern, '', $processed);
                }
            }
        }

        // Clean up any remaining template syntax
        $processed = preg_replace('/\{\{[^}]*\}\}/', '', $processed);

        return trim($processed);
    }

    /**
     * Process Anki cloze cards
     */
    private function processAnkiCloze(array $card, array $fieldMap): array
    {
        $text = '';
        foreach ($fieldMap as $fieldValue) {
            if (preg_match('/\{\{c\d+::[^}]+\}\}/', $fieldValue)) {
                $text = $fieldValue;
                break;
            }
        }

        if (! $text) {
            $text = $card['question'];
        }

        // Convert Anki cloze format to our format
        $clozeText = preg_replace('/\{\{c\d+::([^}]+)\}\}/', '{{$1}}', $text);

        // Extract cloze answers
        preg_match_all('/\{\{c\d+::([^}]+)\}\}/', $text, $matches);
        $clozeAnswers = array_unique($matches[1]);

        $card['cloze_text'] = $clozeText;
        $card['cloze_answers'] = $clozeAnswers;
        $card['question'] = preg_replace('/\{\{[^}]*\}\}/', '[...]', $clozeText);
        $card['answer'] = implode(', ', $clozeAnswers);

        return $card;
    }

    /**
     * Process Anki multiple choice cards
     */
    private function processAnkiMultipleChoice(array $card, array $fieldMap): array
    {
        $choices = [];
        $correctChoices = [];

        // Look for choice fields
        foreach ($fieldMap as $fieldName => $fieldValue) {
            if (stripos($fieldName, 'choice') !== false ||
                stripos($fieldName, 'option') !== false) {
                $choices[] = $fieldValue;
            }
        }

        // If no explicit choices found, try to parse from answer
        if (empty($choices)) {
            $answerText = $card['answer'];
            if (strpos($answerText, ';') !== false) {
                $choices = array_map('trim', explode(';', $answerText));
            } elseif (preg_match_all('/[a-d]\)\s*([^\n]+)/i', $answerText, $matches)) {
                $choices = $matches[1];
            }
        }

        if (! empty($choices)) {
            $card['choices'] = array_slice($choices, 0, 6); // Max 6 choices
            $card['correct_choices'] = [0]; // Default to first choice
        }

        return $card;
    }

    /**
     * Process Anki image occlusion cards
     */
    private function processAnkiImageOcclusion(array $card, array $fieldMap, array $mediaFiles): array
    {
        // Find image field
        $imageField = null;
        foreach ($fieldMap as $fieldName => $fieldValue) {
            if (preg_match('/<img[^>]+src="([^"]+)"/', $fieldValue)) {
                $imageField = $fieldValue;
                break;
            }
        }

        if ($imageField) {
            preg_match('/<img[^>]+src="([^"]+)"/', $imageField, $matches);
            if (isset($matches[1])) {
                $imageSrc = $matches[1];

                // Find corresponding media file
                foreach ($mediaFiles as $mediaFile) {
                    if (strpos($imageSrc, $mediaFile['filename']) !== false) {
                        $card['question_image_url'] = $mediaFile['url'];
                        break;
                    }
                }
            }
        }

        // Basic occlusion data (would need more sophisticated parsing for real occlusions)
        $card['occlusion_data'] = [
            [
                'type' => 'rectangle',
                'x' => 100,
                'y' => 100,
                'width' => 200,
                'height' => 50,
                'answer' => $card['answer'],
            ],
        ];

        return $card;
    }

    /**
     * Extract media references from card content
     */
    private function extractMediaReferences(array $card, array $mediaFiles): array
    {
        $content = $card['question'].' '.$card['answer'];

        // Find image references
        if (preg_match('/<img[^>]+src="([^"]+)"/', $content, $matches)) {
            $imageSrc = $matches[1];
            foreach ($mediaFiles as $mediaFile) {
                if (strpos($imageSrc, $mediaFile['filename']) !== false) {
                    if (! isset($card['question_image_url'])) {
                        $card['question_image_url'] = $mediaFile['url'];
                    }
                    break;
                }
            }
        }

        // Find audio references
        if (preg_match('/\[sound:([^\]]+)\]/', $content, $matches)) {
            $audioFile = $matches[1];
            foreach ($mediaFiles as $mediaFile) {
                if ($mediaFile['filename'] === $audioFile) {
                    $card['audio_url'] = $mediaFile['url'];
                    break;
                }
            }
        }

        return $card;
    }

    /**
     * Extract media files from Anki package
     */
    private function extractMediaFiles(string $extractPath, array $mediaMap, int $unitId): array
    {
        $mediaFiles = [];
        $errors = [];

        foreach ($mediaMap as $fileIndex => $filename) {
            $filePath = $extractPath.'/'.$fileIndex;

            if (! File::exists($filePath)) {
                $errors[] = "Media file not found: {$filename}";

                continue;
            }

            $content = File::get($filePath);
            $storeResult = $this->mediaStorage->storeMediaFile($content, $filename, $unitId);

            if ($storeResult['success']) {
                $mediaFiles[$filename] = $storeResult;
            } else {
                $errors[] = "Failed to store {$filename}: {$storeResult['error']}";
            }
        }

        return [
            'success' => count($mediaFiles) > 0 || count($errors) === 0,
            'media_files' => $mediaFiles,
            'errors' => $errors,
        ];
    }
}
