<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class MnemosyneImportService
{
    /**
     * Parse Mnemosyne .mem XML file and return flashcard data
     */
    public function parseMnemosyneFile(UploadedFile $file): array
    {
        try {
            // Validate file extension
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['mem', 'xml'])) {
                return [
                    'success' => false,
                    'error' => 'File must be a Mnemosyne export (.mem or .xml) file',
                ];
            }

            // Read file content
            $content = file_get_contents($file->getPathname());
            if (empty($content)) {
                return [
                    'success' => false,
                    'error' => 'File is empty or could not be read',
                ];
            }

            return $this->parseMnemosyneContent($content, $file->getClientOriginalName());

        } catch (\Exception $e) {
            Log::error('Mnemosyne import error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse Mnemosyne file: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Parse Mnemosyne XML content
     */
    public function parseMnemosyneContent(string $content, string $filename = 'mnemosyne_export'): array
    {
        try {
            // Handle different Mnemosyne export formats
            $cards = [];

            // Check if it's XML format
            if ($this->isXmlContent($content)) {
                $cards = $this->parseXmlFormat($content);
            } else {
                // Try to parse as text format (older Mnemosyne exports)
                $cards = $this->parseTextFormat($content);
            }

            if (empty($cards)) {
                return [
                    'success' => false,
                    'error' => 'No valid flashcards found in the file',
                ];
            }

            return [
                'success' => true,
                'cards' => $cards,
                'total_cards' => count($cards),
                'categories' => $this->extractCategories($cards),
                'import_source' => 'mnemosyne',
            ];

        } catch (\Exception $e) {
            Log::error('Mnemosyne content parsing error', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse Mnemosyne content: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if content is XML format
     */
    private function isXmlContent(string $content): bool
    {
        return strpos(trim($content), '<?xml') === 0 ||
               strpos(trim($content), '<mnemosyne') !== false ||
               strpos(trim($content), '<cards') !== false;
    }

    /**
     * Parse XML format Mnemosyne export
     */
    private function parseXmlFormat(string $content): array
    {
        $cards = [];

        try {
            // Clean up the XML content
            $content = $this->cleanXmlContent($content);

            // Load XML
            $xml = new SimpleXMLElement($content);

            // Handle different XML structures
            if (isset($xml->card)) {
                // Format: <mnemosyne><card>...</card></mnemosyne>
                foreach ($xml->card as $cardXml) {
                    $card = $this->parseXmlCard($cardXml);
                    if ($card) {
                        $cards[] = $card;
                    }
                }
            } elseif (isset($xml->item)) {
                // Format: <cards><item>...</item></cards>
                foreach ($xml->item as $itemXml) {
                    $card = $this->parseXmlItem($itemXml);
                    if ($card) {
                        $cards[] = $card;
                    }
                }
            } else {
                // Try to find any card-like elements
                $cardElements = $xml->xpath('//card | //item | //flashcard');
                foreach ($cardElements as $cardXml) {
                    $card = $this->parseXmlCard($cardXml);
                    if ($card) {
                        $cards[] = $card;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::warning('XML parsing failed, trying alternative format', [
                'error' => $e->getMessage(),
            ]);

            // If XML parsing fails, try to extract data using regex
            $cards = $this->parseXmlWithRegex($content);
        }

        return $cards;
    }

    /**
     * Parse individual XML card element
     */
    private function parseXmlCard(SimpleXMLElement $cardXml): ?array
    {
        try {
            // Extract question and answer
            $question = $this->getXmlValue($cardXml, ['question', 'q', 'front', 'Q']);
            $answer = $this->getXmlValue($cardXml, ['answer', 'a', 'back', 'A']);

            if (empty(trim($question)) || empty(trim($answer))) {
                return null;
            }

            // Extract optional fields
            $category = $this->getXmlValue($cardXml, ['category', 'cat', 'tag', 'deck']);
            $difficulty = $this->getXmlValue($cardXml, ['difficulty', 'level', 'grade']);

            // Convert Mnemosyne difficulty (0-5) to our scale
            $difficultyLevel = $this->convertDifficulty($difficulty);

            $card = [
                'card_type' => 'basic',
                'question' => $this->cleanText($question),
                'answer' => $this->cleanText($answer),
                'hint' => null,
                'difficulty_level' => $difficultyLevel,
                'tags' => $category ? [$category] : [],
                'import_source' => 'mnemosyne',
                'mnemosyne_data' => [
                    'category' => $category,
                    'original_difficulty' => $difficulty,
                ],
            ];

            // Check for additional fields
            $hint = $this->getXmlValue($cardXml, ['hint', 'note', 'comment']);
            if ($hint) {
                $card['hint'] = $this->cleanText($hint);
            }

            return $card;

        } catch (\Exception $e) {
            Log::warning('Failed to parse XML card', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse XML item element (alternative format)
     */
    private function parseXmlItem(SimpleXMLElement $itemXml): ?array
    {
        try {
            // Mnemosyne sometimes uses different field names in item format
            $question = $this->getXmlValue($itemXml, ['text', 'question', 'front']);
            $answer = $this->getXmlValue($itemXml, ['answer', 'back', 'solution']);

            // Sometimes the entire content is in a single field, separated by delimiters
            if (empty($answer) && ! empty($question)) {
                $parts = $this->splitQuestionAnswer($question);
                if ($parts) {
                    $question = $parts['question'];
                    $answer = $parts['answer'];
                }
            }

            if (empty(trim($question)) || empty(trim($answer))) {
                return null;
            }

            return [
                'card_type' => 'basic',
                'question' => $this->cleanText($question),
                'answer' => $this->cleanText($answer),
                'hint' => null,
                'difficulty_level' => 'medium',
                'tags' => [],
                'import_source' => 'mnemosyne',
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse XML item', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse XML content using regex (fallback method)
     */
    private function parseXmlWithRegex(string $content): array
    {
        $cards = [];

        // Pattern to match card-like elements
        $patterns = [
            // <card><question>Q</question><answer>A</answer></card>
            '/<(?:card|item|flashcard)[^>]*>.*?<(?:question|q|front)>(.*?)<\/(?:question|q|front)>.*?<(?:answer|a|back)>(.*?)<\/(?:answer|a|back)>.*?<\/(?:card|item|flashcard)>/is',
            // <Q>question</Q><A>answer</A>
            '/<Q>(.*?)<\/Q>\s*<A>(.*?)<\/A>/is',
            // Simpler pattern for basic XML
            '/<question>(.*?)<\/question>\s*<answer>(.*?)<\/answer>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if (count($match) >= 3) {
                        $question = $this->cleanText($match[1]);
                        $answer = $this->cleanText($match[2]);

                        if (! empty(trim($question)) && ! empty(trim($answer))) {
                            $cards[] = [
                                'card_type' => 'basic',
                                'question' => $question,
                                'answer' => $answer,
                                'hint' => null,
                                'difficulty_level' => 'medium',
                                'tags' => [],
                                'import_source' => 'mnemosyne',
                            ];
                        }
                    }
                }

                if (! empty($cards)) {
                    break; // Stop after first successful pattern
                }
            }
        }

        return $cards;
    }

    /**
     * Parse text format Mnemosyne export
     */
    private function parseTextFormat(string $content): array
    {
        $cards = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Skip empty lines and comments
            }

            $card = $this->parseTextLine($line);
            if ($card) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * Parse a single text line from Mnemosyne export
     */
    private function parseTextLine(string $line): ?array
    {
        // Try different delimiters
        $delimiters = ["\t", ' | ', ' - ', ';', '|'];

        foreach ($delimiters as $delimiter) {
            if (strpos($line, $delimiter) !== false) {
                $parts = explode($delimiter, $line, 2);

                if (count($parts) >= 2) {
                    $question = trim($parts[0]);
                    $answer = trim($parts[1]);

                    if (! empty($question) && ! empty($answer)) {
                        return [
                            'card_type' => 'basic',
                            'question' => $this->cleanText($question),
                            'answer' => $this->cleanText($answer),
                            'hint' => null,
                            'difficulty_level' => 'medium',
                            'tags' => [],
                            'import_source' => 'mnemosyne',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get value from XML element using multiple possible field names
     */
    private function getXmlValue(SimpleXMLElement $xml, array $fieldNames): string
    {
        foreach ($fieldNames as $fieldName) {
            if (isset($xml->$fieldName)) {
                return (string) $xml->$fieldName;
            }

            // Try attributes
            if (isset($xml[$fieldName])) {
                return (string) $xml[$fieldName];
            }
        }

        return '';
    }

    /**
     * Clean XML content for parsing
     */
    private function cleanXmlContent(string $content): string
    {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Fix common XML issues
        $content = str_replace(['&', '<br>', '<BR>'], ['&amp;', "\n", "\n"], $content);

        // Ensure XML declaration exists
        if (strpos($content, '<?xml') === false) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n".$content;
        }

        return $content;
    }

    /**
     * Split combined question-answer text
     */
    private function splitQuestionAnswer(string $text): ?array
    {
        // Try different patterns to split Q&A
        $patterns = [
            '/(.+?)\s*[\?\:]\s*(.+)/',  // Question? Answer or Question: Answer
            '/(.+?)\s*->\s*(.+)/',      // Question -> Answer
            '/(.+?)\s*=\s*(.+)/',       // Question = Answer
            '/(.{1,200}?)\s+(.{10,})/',  // First part (up to 200 chars) + rest
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) && count($matches) >= 3) {
                $question = trim($matches[1]);
                $answer = trim($matches[2]);

                if (strlen($question) > 3 && strlen($answer) > 3) {
                    return ['question' => $question, 'answer' => $answer];
                }
            }
        }

        return null;
    }

    /**
     * Clean text content
     */
    private function cleanText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text, '<b><i><u><br>');

        // Convert HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML401, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Convert Mnemosyne difficulty to our scale
     */
    private function convertDifficulty(?string $difficulty): string
    {
        if (! $difficulty || ! is_numeric($difficulty)) {
            return 'medium';
        }

        $level = (int) $difficulty;

        // Mnemosyne uses 0-5 scale
        if ($level <= 1) {
            return 'easy';
        } elseif ($level >= 4) {
            return 'hard';
        } else {
            return 'medium';
        }
    }

    /**
     * Extract categories from cards
     */
    private function extractCategories(array $cards): array
    {
        $categories = [];

        foreach ($cards as $card) {
            if (! empty($card['tags'])) {
                $categories = array_merge($categories, $card['tags']);
            }

            if (isset($card['mnemosyne_data']['category']) && $card['mnemosyne_data']['category']) {
                $categories[] = $card['mnemosyne_data']['category'];
            }
        }

        return array_unique(array_filter($categories));
    }

    /**
     * Validate Mnemosyne file
     */
    public function validateMnemosyneFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['mem', 'xml'])) {
            return [
                'valid' => false,
                'error' => 'File must have .mem or .xml extension',
            ];
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            return [
                'valid' => false,
                'error' => 'File size must be less than 10MB',
            ];
        }

        // Try to read a small sample to validate format
        try {
            $handle = fopen($file->getPathname(), 'r');
            $sample = fread($handle, 1024); // Read first 1KB
            fclose($handle);

            // Check if it looks like Mnemosyne format
            $isMnemosyne = $this->isXmlContent($sample) ||
                          strpos($sample, "\t") !== false ||
                          strpos($sample, ' | ') !== false;

            if (! $isMnemosyne) {
                return [
                    'valid' => false,
                    'error' => 'File does not appear to be a valid Mnemosyne export',
                ];
            }

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Unable to read file: '.$e->getMessage(),
            ];
        }

        return [
            'valid' => true,
            'file_size' => $file->getSize(),
            'extension' => $extension,
        ];
    }
}
