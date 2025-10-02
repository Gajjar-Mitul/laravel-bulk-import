<?php

// app/Services/ImageProcessingService.php

namespace App\Domains\FileUploads\Services;

use App\Domains\FileUploads\Models\Image;
use App\Domains\FileUploads\Models\Upload;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    /**
     * @var array<string, int|null>
     */
    protected array $variants = [
        'original' => null, // No resizing
        '256px' => 256,
        '512px' => 512,
        '1024px' => 1024,
    ];

    protected string $storageDir = 'uploads/images';

    /**
     * @return array<Image>
     */
    public function processImageVariants(Upload $upload, ?string $imageableType, ?int $imageableId, bool $isPrimary = false): array
    {
        throw_unless($upload->isCompleted(), new Exception('Upload is not completed'));

        $images = [];

        try {
            // Remove existing primary images if this is primary
            if ($isPrimary && $imageableType !== null && $imageableId !== null) {
                $this->removePrimaryImages($imageableType, $imageableId);
            }

            foreach ($this->variants as $variant => $size) {
                $image = $this->createImageVariant($upload, $variant, $size, $imageableType, $imageableId, $isPrimary);
                $images[] = $image;
            }

            Log::info('Image variants processed successfully', [
                'upload_uuid' => $upload->uuid,
                'variants_count' => count($images),
            ]);

            return $images;

        } catch (Exception $exception) {
            // Clean up any created images on failure
            foreach ($images as $image) {
                $this->deleteImage($image);
            }

            Log::error('Image processing failed', [
                'upload_uuid' => $upload->uuid,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function createImageVariant(
        Upload $upload,
        string $variant,
        ?int $size,
        ?string $imageableType = null,
        ?int $imageableId = null,
        bool $isPrimary = false
    ): Image {
        $originalPath = $upload->storage_path;

        $originalPathString = $originalPath;
        throw_if($originalPathString === null, new Exception('Upload storage path is null'));

        throw_unless(Storage::exists($originalPathString), new Exception('Original image file not found: '.$originalPathString));

        // Generate variant filename
        $extension = pathinfo($upload->original_filename, PATHINFO_EXTENSION);
        $variantFilename = $upload->uuid.sprintf('_%s.', $variant).$extension;
        $variantPath = $this->storageDir.'/variants/'.$variantFilename;

        if ($variant === 'original') {
            // For original, just copy the file
            Storage::copy($originalPathString, $variantPath);

            // Get original dimensions
            $storagePath = Storage::path($originalPathString);

            $imageInfo = getimagesize($storagePath);
            throw_if($imageInfo === false, new Exception('Could not get image dimensions for: '.$storagePath));

            $width = $imageInfo[0];  // Width is at index 0
            $height = $imageInfo[1]; // Height is at index 1
        } else {
            // Create resized variant
            $manager = new ImageManager(new Driver);
            $storagePath = Storage::path($originalPathString);

            $image = $manager->read($storagePath);

            // Resize maintaining aspect ratio
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            if ($originalWidth > $originalHeight) {
                $image->scaleDown($size, null);
            } else {
                $image->scaleDown(null, $size);
            }

            $width = $image->width();
            $height = $image->height();

            // Save variant
            Storage::put($variantPath, $image->encode());
        }

        // Create Image record
        return Image::query()->create([
            'upload_id' => $upload->id,
            'imageable_type' => $imageableType,
            'imageable_id' => $imageableId,
            'is_primary' => $isPrimary,
            'variant' => $variant,
            'storage_path' => $variantPath,
            'width' => $width,
            'height' => $height,
            'file_size' => Storage::size($variantPath),
            'mime_type' => $upload->mime_type,
        ]);
    }

    protected function removePrimaryImages(string $imageableType, int $imageableId): void
    {
        $existingImages = Image::query()->where('imageable_type', $imageableType)
            ->where('imageable_id', $imageableId)
            ->where('is_primary', true)
            ->get();

        foreach ($existingImages as $image) {
            $this->deleteImage($image);
        }
    }

    protected function deleteImage(Image $image): void
    {
        // Delete file from storage
        if (Storage::exists($image->storage_path)) {
            Storage::delete($image->storage_path);
        }

        // Delete database record
        $image->delete();
    }

    public function attachImageToEntity(Upload $upload, string $entityType, int $entityId, bool $isPrimary = false): array
    {
        // Check if this exact upload is already attached to this entity as primary
        $existingAttachment = Image::query()->where('upload_id', $upload->id)
            ->where('imageable_type', $entityType)
            ->where('imageable_id', $entityId)
            ->where('is_primary', $isPrimary)
            ->exists();

        if ($existingAttachment) {
            // Idempotent - return existing images
            return Image::query()->where('upload_id', $upload->id)
                ->where('imageable_type', $entityType)
                ->where('imageable_id', $entityId)
                ->get()
                ->toArray();
        }

        return $this->processImageVariants($upload, $entityType, $entityId, $isPrimary);
    }

    /**
     * Process uploaded image to create Image records without attaching to entity
     */
    public function processUploadedImage(Upload $upload): array
    {
        throw_unless($upload->isCompleted(), new Exception('Upload is not completed'));

        $images = [];

        try {
            // Generate variants for standalone upload
            foreach ($this->variants as $variantName => $maxSize) {
                $image = $this->createImageVariant(
                    $upload,
                    $variantName,
                    $maxSize,
                    null, // No entity type for standalone uploads
                    null, // No entity ID for standalone uploads
                    false // Not primary for standalone uploads
                );
                if ($image) {
                    $images[] = $image;
                    Log::info('Image variant created', [
                        'upload_uuid' => $upload->uuid,
                        'variant' => $variantName,
                        'path' => $image->storage_path,
                    ]);
                }
            }

            return $images;

        } catch (Exception $exception) {
            Log::error('Image processing failed', [
                'upload_uuid' => $upload->uuid,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
