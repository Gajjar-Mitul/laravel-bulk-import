<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\Models\Upload;
use App\Domains\Products\Queries\ProductImageQueries;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    private array $variants = [
        'original' => null,
        '256px' => 256,
        '512px' => 512,
        '1024px' => 1024,
    ];

    public function __construct(
        private readonly ProductImageQueries $queries
    ) {}

    public function generateVariants(Upload $upload, int $productId): array
    {
        return DB::transaction(function () use ($upload, $productId) {
            // Re-attaching same upload = no-op
            $existing = $this->queries->getByUpload($upload->id);
            if ($existing->where('product_id', $productId)->count() > 0) {
                return $existing->toArray();
            }

            $originalPath = storage_path('app/public/'.($upload->storage_path ?? 'default.jpg'));
            $manager = new ImageManager(new Driver);
            $createdImages = [];

            foreach ($this->variants as $variant => $size) {
                $variantPath = 'images/products/'.Carbon::now()->getTimestamp().sprintf('_%s_', $variant).basename($upload->storage_path ?? 'default.jpg');
                $fullPath = storage_path('app/public/'.$variantPath);

                if ($variant === 'original') {
                    copy($originalPath, $fullPath);
                    $dimensions = getimagesize($fullPath);
                    throw_if($dimensions === false, new Exception('Could not get image dimensions'));

                    $width = $dimensions[0];
                    $height = $dimensions[1];
                } else {
                    // Proper resize with aspect ratio
                    $image = $manager->read($originalPath);
                    $image->scale(width: $size); // Maintains aspect ratio
                    $image->save($fullPath);
                    $width = $image->width();
                    $height = $image->height();
                }

                $productImage = $this->queries->create([
                    'product_id' => $productId,
                    'upload_id' => $upload->id,
                    'variant' => $variant,
                    'path' => $variantPath,
                    'width' => $width,
                    'height' => $height,
                    'size' => filesize($fullPath),
                    'is_primary' => $variant === 'original',
                ]);

                $createdImages[] = $productImage;
            }

            return $createdImages;
        }, 5); // Concurrency safe with retries
    }

    // Process image from URL (for CSV imports)
    // DISABLED FOR PERFORMANCE - Image processing is skipped during import
    public function processImageFromUrl(string $imageUrl, int $productId): array
    {
        // Log when this method is called to help debug unwanted calls
        Log::warning('ImageProcessingService::processImageFromUrl called - DISABLED FOR PERFORMANCE', [
            'url' => $imageUrl,
            'product_id' => $productId,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ]);

        // Return empty array instead of processing images
        // This prevents timeout errors and improves import performance
        return [];
    }
}
