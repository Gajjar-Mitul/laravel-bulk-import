<?php

namespace App\Domains\FileUploads\Controllers\Api;

use App\Domains\FileUploads\Models\Upload;
use App\Domains\FileUploads\Services\ChunkedUploadService;
use App\Domains\FileUploads\Services\ImageProcessingService;
use App\Domains\Products\Models\Product;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ChunkedUploadController extends Controller
{
    public function __construct(
        protected ChunkedUploadService $uploadService,
        protected ImageProcessingService $imageService
    ) {}

    /**
     * Initialize chunked upload
     */
    public function initializeUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'total_size' => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1',
            'mime_type' => 'required|string|in:image/jpeg,image/png,image/gif,image/webp',
            'checksum' => 'nullable|string|size:64', // SHA256 hash
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $upload = $this->uploadService->initializeUpload(
                $request->filename,
                $request->total_size,
                $request->total_chunks,
                $request->mime_type,
                $request->checksum
            );

            return response()->json([
                'success' => true,
                'message' => 'Upload initialized successfully',
                'data' => [
                    'upload_uuid' => $upload->uuid,
                    'total_chunks' => $upload->total_chunks,
                    'status' => $upload->status,
                ],
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize upload',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a chunk
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_uuid' => 'required|string|uuid',
            'chunk_index' => 'required|integer|min:0',
            'chunk_size' => 'required|integer|min:1',
            'chunk_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $upload = Upload::byUuid($request->upload_uuid)->first();

            if (! $upload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload not found',
                ], 404);
            }

            $success = $this->uploadService->uploadChunk(
                $upload,
                $request->file('chunk_file'),
                $request->chunk_index,
                $request->chunk_size
            );

            // Refresh upload to get latest status
            $upload = $upload->fresh();

            if (! $upload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload not found after refresh',
                ], 404);
            }

            return response()->json([
                'success' => $success,
                'message' => 'Chunk uploaded successfully',
                'data' => [
                    'upload_uuid' => $upload->uuid,
                    'uploaded_chunks' => $upload->uploaded_chunks,
                    'total_chunks' => $upload->total_chunks,
                    'progress_percentage' => $upload->getProgressPercentage(),
                    'status' => $upload->status,
                    'is_completed' => $upload->isCompleted(),
                ],
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Chunk upload failed',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status
     */
    public function getUploadStatus(string $uuid): JsonResponse
    {
        $upload = $this->uploadService->getUploadStatus($uuid);

        if (! $upload instanceof Upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'upload_uuid' => $upload->uuid,
                'original_filename' => $upload->original_filename,
                'total_size' => $upload->total_size,
                'uploaded_chunks' => $upload->uploaded_chunks,
                'total_chunks' => $upload->total_chunks,
                'progress_percentage' => $upload->getProgressPercentage(),
                'status' => $upload->status,
                'is_completed' => $upload->isCompleted(),
                'created_at' => $upload->created_at,
                'completed_at' => $upload->completed_at,
            ],
        ]);
    }

    /**
     * Attach uploaded image to product
     */
    public function attachImageToProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_uuid' => 'required|string|uuid',
            'product_sku' => 'required|string|exists:products,sku',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $upload = Upload::byUuid($request->upload_uuid)->first();

            if (! $upload || ! $upload->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload not found or not completed',
                ], 404);
            }

            $product = Product::query()->where('sku', $request->product_sku)->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $isPrimary = $request->boolean('is_primary', false);

            $images = $this->imageService->attachImageToEntity(
                $upload,
                Product::class,
                $product->id,
                $isPrimary
            );

            return response()->json([
                'success' => true,
                'message' => 'Image attached to product successfully',
                'data' => [
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'is_primary' => $isPrimary,
                    'images_created' => count($images),
                    'variants' => new Collection($images)->pluck('variant')->toArray(),
                ],
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach image to product',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel upload
     */
    public function cancelUpload(string $uuid): JsonResponse
    {
        try {
            $success = $this->uploadService->cancelUpload($uuid);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Upload cancelled successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Upload not found or cannot be cancelled',
            ], 404);

        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel upload',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
