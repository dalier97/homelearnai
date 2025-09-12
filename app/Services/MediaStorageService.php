<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use ZipArchive;

class MediaStorageService
{
    /**
     * Supported image formats
     */
    private const SUPPORTED_IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    /**
     * Supported audio formats
     */
    private const SUPPORTED_AUDIO = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

    /**
     * Supported video formats
     */
    private const SUPPORTED_VIDEO = ['mp4', 'webm', 'ogg', 'avi', 'mov'];

    /**
     * Maximum file size in bytes (50MB)
     */
    private const MAX_FILE_SIZE = 50 * 1024 * 1024;

    /**
     * Maximum image width for thumbnails
     */
    private const THUMBNAIL_WIDTH = 300;

    /**
     * Maximum image height for thumbnails
     */
    private const THUMBNAIL_HEIGHT = 300;

    private ?ImageManager $imageManager;

    public function __construct()
    {
        try {
            $this->imageManager = new ImageManager(new Driver);
        } catch (\Exception $e) {
            // GD extension not available - log warning but don't fail
            Log::warning('Image processing disabled: GD extension not available. '.$e->getMessage());
            $this->imageManager = null;
        }
    }

    /**
     * Store media file and return the file path
     *
     * @param  string  $content  File content
     * @param  string  $originalName  Original filename
     * @param  int  $unitId  Unit ID for organizing files
     */
    public function storeMediaFile(string $content, string $originalName, int $unitId): array
    {
        try {
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileSize = strlen($content);

            // Validate file size
            if ($fileSize > self::MAX_FILE_SIZE) {
                return [
                    'success' => false,
                    'error' => 'File size exceeds maximum limit of 50MB',
                ];
            }

            // Validate file type
            $mediaType = $this->getMediaType($extension);
            if (! $mediaType) {
                return [
                    'success' => false,
                    'error' => "Unsupported file type: {$extension}",
                ];
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($originalName, $unitId);
            $storagePath = "flashcard-media/unit-{$unitId}/{$filename}";

            // Store the file
            if (! Storage::disk('public')->put($storagePath, $content)) {
                return [
                    'success' => false,
                    'error' => 'Failed to store media file',
                ];
            }

            $result = [
                'success' => true,
                'path' => $storagePath,
                'url' => Storage::disk('public')->url($storagePath),
                'filename' => $filename,
                'original_name' => $originalName,
                'media_type' => $mediaType,
                'file_size' => $fileSize,
                'extension' => $extension,
            ];

            // Generate thumbnail for images
            if ($mediaType === 'image' && in_array($extension, self::SUPPORTED_IMAGES)) {
                $thumbnailResult = $this->generateThumbnail($content, $filename, $unitId);
                if ($thumbnailResult['success']) {
                    $result['thumbnail_path'] = $thumbnailResult['path'];
                    $result['thumbnail_url'] = $thumbnailResult['url'];
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Media storage error', [
                'original_name' => $originalName,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process media file: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Extract media files from ZIP archive (Anki .apkg format)
     *
     * @param  string  $zipPath  Path to ZIP file
     * @param  int  $unitId  Unit ID for organizing files
     */
    public function extractMediaFromZip(string $zipPath, int $unitId): array
    {
        $mediaFiles = [];
        $errors = [];

        try {
            $zip = new ZipArchive;
            $result = $zip->open($zipPath);

            if ($result !== true) {
                return [
                    'success' => false,
                    'error' => 'Failed to open ZIP archive',
                    'media_files' => [],
                ];
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Skip directories and system files
                if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX') !== false) {
                    continue;
                }

                // Check if it's a media file
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (! $this->getMediaType($extension)) {
                    continue;
                }

                // Extract file content
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    $errors[] = "Failed to extract file: {$filename}";

                    continue;
                }

                // Store the media file
                $storeResult = $this->storeMediaFile($content, basename($filename), $unitId);
                if ($storeResult['success']) {
                    $mediaFiles[$filename] = $storeResult;
                } else {
                    $errors[] = "Failed to store {$filename}: {$storeResult['error']}";
                }
            }

            $zip->close();

            return [
                'success' => count($mediaFiles) > 0 || count($errors) === 0,
                'media_files' => $mediaFiles,
                'errors' => $errors,
                'extracted_count' => count($mediaFiles),
                'error_count' => count($errors),
            ];

        } catch (\Exception $e) {
            Log::error('Media extraction error', [
                'zip_path' => $zipPath,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to extract media files: '.$e->getMessage(),
                'media_files' => [],
            ];
        }
    }

    /**
     * Check if media file already exists and return existing path
     *
     * @param  string  $content  File content
     * @param  string  $originalName  Original filename
     * @param  int  $unitId  Unit ID
     */
    public function findExistingMedia(string $content, string $originalName, int $unitId): ?array
    {
        $hash = md5($content);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Check for files with same hash in the same unit
        $directory = storage_path("app/public/flashcard-media/unit-{$unitId}");

        if (! File::isDirectory($directory)) {
            return null;
        }

        $files = File::files($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === $extension) {
                $existingHash = md5(File::get($file->getPathname()));
                if ($existingHash === $hash) {
                    $relativePath = str_replace(storage_path('app/public/'), '', $file->getPathname());

                    return [
                        'success' => true,
                        'path' => $relativePath,
                        'url' => Storage::disk('public')->url($relativePath),
                        'filename' => $file->getFilename(),
                        'original_name' => $originalName,
                        'media_type' => $this->getMediaType($extension),
                        'file_size' => $file->getSize(),
                        'extension' => $extension,
                        'is_duplicate' => true,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Clean up unused media files for a unit
     *
     * @param  int  $unitId  Unit ID
     */
    public function cleanupUnusedMedia(int $unitId): array
    {
        try {
            $directory = "flashcard-media/unit-{$unitId}";

            if (! Storage::disk('public')->exists($directory)) {
                return [
                    'success' => true,
                    'deleted_files' => 0,
                    'message' => 'No media directory found',
                ];
            }

            // Get all media files in the directory
            $mediaFiles = Storage::disk('public')->allFiles($directory);

            // Get all media references from flashcards in this unit
            $flashcards = \App\Models\Flashcard::where('unit_id', $unitId)->get();
            $referencedFiles = [];

            foreach ($flashcards as $flashcard) {
                if ($flashcard->question_image_url) {
                    $referencedFiles[] = $this->extractPathFromUrl($flashcard->question_image_url);
                }
                if ($flashcard->answer_image_url) {
                    $referencedFiles[] = $this->extractPathFromUrl($flashcard->answer_image_url);
                }

                // Check occlusion data for image references
                if ($flashcard->occlusion_data) {
                    foreach ($flashcard->occlusion_data as $occlusion) {
                        if (isset($occlusion['image_url'])) {
                            $referencedFiles[] = $this->extractPathFromUrl($occlusion['image_url']);
                        }
                    }
                }
            }

            $referencedFiles = array_filter(array_unique($referencedFiles));
            $deletedCount = 0;

            // Delete unreferenced files
            foreach ($mediaFiles as $mediaFile) {
                if (! in_array($mediaFile, $referencedFiles)) {
                    Storage::disk('public')->delete($mediaFile);
                    $deletedCount++;
                }
            }

            return [
                'success' => true,
                'deleted_files' => $deletedCount,
                'total_files' => count($mediaFiles),
                'referenced_files' => count($referencedFiles),
            ];

        } catch (\Exception $e) {
            Log::error('Media cleanup error', [
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to cleanup media files: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Generate thumbnail for image
     *
     * @param  string  $content  Image content
     * @param  string  $filename  Original filename
     * @param  int  $unitId  Unit ID
     */
    private function generateThumbnail(string $content, string $filename, int $unitId): array
    {
        try {
            // Skip thumbnail generation if image processing is not available
            if ($this->imageManager === null) {
                Log::info('Skipping thumbnail generation - image processing not available');

                return [
                    'thumbnail_path' => null,
                    'thumbnail_url' => null,
                ];
            }

            $image = $this->imageManager->read($content);

            // Resize maintaining aspect ratio
            $image->scaleDown(self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT);

            // Generate thumbnail filename
            $thumbnailFilename = pathinfo($filename, PATHINFO_FILENAME).'_thumb.'.
                                 pathinfo($filename, PATHINFO_EXTENSION);
            $thumbnailPath = "flashcard-media/unit-{$unitId}/thumbnails/{$thumbnailFilename}";

            // Save thumbnail
            $thumbnailContent = $image->toJpeg(80)->toString();
            Storage::disk('public')->put($thumbnailPath, $thumbnailContent);

            return [
                'success' => true,
                'path' => $thumbnailPath,
                'url' => Storage::disk('public')->url($thumbnailPath),
            ];

        } catch (\Exception $e) {
            Log::error('Thumbnail generation error', [
                'filename' => $filename,
                'unit_id' => $unitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate thumbnail',
            ];
        }
    }

    /**
     * Generate unique filename for media file
     *
     * @param  string  $originalName  Original filename
     * @param  int  $unitId  Unit ID
     */
    private function generateUniqueFilename(string $originalName, int $unitId): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize filename
        $basename = Str::slug($basename);
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$basename}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get media type from file extension
     *
     * @param  string  $extension  File extension
     */
    private function getMediaType(string $extension): ?string
    {
        $extension = strtolower($extension);

        if (in_array($extension, self::SUPPORTED_IMAGES)) {
            return 'image';
        }

        if (in_array($extension, self::SUPPORTED_AUDIO)) {
            return 'audio';
        }

        if (in_array($extension, self::SUPPORTED_VIDEO)) {
            return 'video';
        }

        return null;
    }

    /**
     * Extract file path from storage URL
     *
     * @param  string  $url  Storage URL
     */
    private function extractPathFromUrl(string $url): string
    {
        // Remove the base URL to get just the path
        $basePath = config('app.url').'/storage/';

        return str_replace($basePath, '', $url);
    }

    /**
     * Get supported file extensions
     */
    public static function getSupportedExtensions(): array
    {
        return array_merge(self::SUPPORTED_IMAGES, self::SUPPORTED_AUDIO, self::SUPPORTED_VIDEO);
    }

    /**
     * Validate media file
     */
    public function validateMediaFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! $this->getMediaType($extension)) {
            return [
                'valid' => false,
                'error' => "Unsupported file type: {$extension}",
            ];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum limit of 50MB',
            ];
        }

        return [
            'valid' => true,
            'media_type' => $this->getMediaType($extension),
            'file_size' => $file->getSize(),
            'extension' => $extension,
        ];
    }
}
