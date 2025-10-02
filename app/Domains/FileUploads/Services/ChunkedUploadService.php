<?php

namespace App\Domains\FileUploads\Services;

use App\Domains\FileUploads\Models\Upload;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ChunkedUploadService
{
    protected string $tempDir = 'uploads/temp';

    protected string $finalDir = 'uploads/images';

    public function __construct(
        protected ImageProcessingService $imageService
    ) {}

    public function initializeUpload(
        string $filename,
        int $totalSize,
        int $totalChunks,
        string $mimeType,
        ?string $checksum = null
    ): Upload {
        return Upload::query()->create([
            'uuid' => (string) Str::uuid(),
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'total_size' => $totalSize,
            'total_chunks' => $totalChunks,
            'checksum' => $checksum,
            'status' => 'pending',
            'chunk_info' => array_fill(0, $totalChunks, false),
        ]);
    }

    public function uploadChunk(
        Upload $upload,
        UploadedFile $chunkFile,
        int $chunkIndex,
        int $chunkSize
    ): bool {
        try {
            // Validate chunk index
            throw_if($chunkIndex < 0 || $chunkIndex >= $upload->total_chunks, new InvalidArgumentException('Invalid chunk index'));

            // Check if chunk already uploaded (idempotent)
            if ($upload->isChunkUploaded($chunkIndex)) {
                Log::info('Chunk already uploaded', [
                    'upload_uuid' => $upload->uuid,
                    'chunk_index' => $chunkIndex,
                ]);

                return true;
            }

            // Store chunk temporarily
            $tempPath = $this->tempDir.'/'.$upload->uuid;
            $chunkFilename = 'chunk_'.$chunkIndex;

            Storage::putFileAs($tempPath, $chunkFile, $chunkFilename);

            // Verify chunk size
            $storedChunkPath = $tempPath.'/'.$chunkFilename;
            $actualSize = Storage::size($storedChunkPath);

            if ($actualSize !== $chunkSize) {
                Storage::delete($storedChunkPath);
                throw new Exception('Chunk size mismatch');
            }

            // Mark chunk as uploaded
            $upload->markChunkUploaded($chunkIndex);

            // Check if all chunks are uploaded
            $refreshedUpload = $upload->fresh();
            if ($refreshedUpload !== null && $refreshedUpload->isCompleted()) {
                $this->assembleFile($upload);
            }

            return true;

        } catch (Exception $exception) {
            Log::error('Chunk upload failed', [
                'upload_uuid' => $upload->uuid,
                'chunk_index' => $chunkIndex,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function assembleFile(Upload $upload): void
    {
        try {
            $tempPath = $this->tempDir.'/'.$upload->uuid;
            $finalFilename = $upload->uuid.'_'.$this->sanitizeFilename($upload->original_filename);
            $finalPath = $this->finalDir.'/'.$finalFilename;

            // Create temporary file to assemble chunks
            $tempFile = tempnam(sys_get_temp_dir(), 'upload_assembly');

            for ($i = 0; $i < $upload->total_chunks; $i++) {
                $chunkPath = $tempPath.('/chunk_'.$i);

                throw_unless(Storage::exists($chunkPath), new Exception('Missing chunk '.$i));

                $chunkContent = Storage::get($chunkPath);
                file_put_contents($tempFile, $chunkContent, FILE_APPEND | LOCK_EX);
            }

            // Verify file size
            $assembledSize = filesize($tempFile);
            if ($assembledSize !== $upload->total_size) {
                unlink($tempFile);
                throw new Exception('Assembled file size mismatch');
            }

            // Verify checksum if provided
            if ($upload->checksum) {
                $actualChecksum = hash_file('sha256', $tempFile);
                if ($actualChecksum !== $upload->checksum) {
                    unlink($tempFile);
                    throw new Exception('Checksum verification failed');
                }
            }

            // Move to final storage
            $tempFileContent = file_get_contents($tempFile);
            if ($tempFileContent === false) {
                unlink($tempFile);
                throw new Exception('Failed to read assembled file');
            }

            Storage::put($finalPath, $tempFileContent);
            unlink($tempFile);

            // Clean up temp chunks
            for ($i = 0; $i < $upload->total_chunks; $i++) {
                Storage::delete($tempPath.('/chunk_'.$i));
            }

            // Remove temp directory if empty
            if (empty(Storage::allFiles($tempPath))) {
                Storage::deleteDirectory($tempPath);
            }

            // Update upload record
            $upload->update([
                'storage_path' => $finalPath,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create Image records with variants if it's an image file
            if ($this->isImageFile($upload->mime_type)) {
                $this->imageService->processUploadedImage($upload);
                $refreshedUploadForImages = $upload->fresh();
                Log::info('Image processing completed', [
                    'upload_uuid' => $upload->uuid,
                    'images_created' => $refreshedUploadForImages !== null ? $refreshedUploadForImages->images()->count() : 0,
                ]);
            }

            Log::info('File assembly completed', [
                'upload_uuid' => $upload->uuid,
                'final_path' => $finalPath,
            ]);

        } catch (Exception $exception) {
            $upload->update([
                'status' => 'failed',
            ]);

            Log::error('File assembly failed', [
                'upload_uuid' => $upload->uuid,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getUploadStatus(string $uuid): ?Upload
    {
        return Upload::byUuid($uuid)->first();
    }

    public function resumeUpload(string $uuid): ?Upload
    {
        $upload = Upload::byUuid($uuid)->first();

        if (! $upload) {
            return null;
        }

        // Reset status to uploading if it was failed
        if ($upload->status === 'failed') {
            $upload->update([
                'status' => 'uploading',
            ]);
        }

        return $upload;
    }

    public function cancelUpload(string $uuid): bool
    {
        $upload = Upload::byUuid($uuid)->first();

        if (! $upload) {
            return false;
        }

        try {
            // Clean up temp files
            $tempPath = $this->tempDir.'/'.$upload->uuid;
            Storage::deleteDirectory($tempPath);

            // Clean up final file if exists
            if ($upload->storage_path) {
                Storage::delete($upload->storage_path);
            }

            // Delete upload record
            $upload->delete();

            return true;
        } catch (Exception $exception) {
            Log::error('Upload cancellation failed', [
                'upload_uuid' => $uuid,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the file is an image
     */
    protected function isImageFile(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    /**
     * Sanitize filename by removing query parameters and invalid characters
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove query parameters (everything after ?)
        $filename = explode('?', $filename)[0];

        // Remove any URL fragments (everything after #)
        $filename = explode('#', $filename)[0];

        // Remove or replace invalid characters for filesystem
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);

        // Ensure we have an extension
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.jpg'; // Default to jpg if no extension
        }

        return $filename;
    }
}
