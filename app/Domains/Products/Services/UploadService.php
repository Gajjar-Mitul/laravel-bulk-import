<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\DataObjects\ChunkUploadData;
use App\Domains\Products\DataObjects\ImageUploadInitData;
use App\Domains\Products\Models\Upload;
use App\Domains\Products\Models\UploadChunk;
use App\Domains\Products\Queries\UploadQueries;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function __construct(
        private readonly UploadQueries $queries
    ) {}

    public function initializeUpload(ImageUploadInitData $data): Upload
    {
        return $this->queries->create([
            'filename' => Carbon::now()->getTimestamp().'_'.$data->filename,
            'original_filename' => $data->filename,
            'total_size' => $data->total_size,
            'total_chunks' => $data->total_chunks,
            'checksum' => $data->checksum,
            'status' => 'pending',
        ]);
    }

    public function uploadChunk(ChunkUploadData $data): array
    {
        $upload = $this->queries->findById($data->upload_id);
        throw_unless($upload instanceof Upload, new Exception('Upload not found'));

        // Verify checksum
        $uploadedChecksum = md5_file($data->chunk_data->getRealPath());
        throw_if($uploadedChecksum !== $data->checksum, new Exception('Checksum mismatch'));

        // Store chunk
        $chunkPath = sprintf('chunks/%s/%d', $upload->id, $data->chunk_number);
        Storage::disk('local')->putFileAs(
            dirname($chunkPath),
            $data->chunk_data,
            basename($chunkPath)
        );

        // Update chunk record
        $chunk = $this->queries->findChunk($data->upload_id, $data->chunk_number);
        if ($chunk instanceof UploadChunk) {
            $chunk->update([
                'size' => $data->chunk_data->getSize(),
                'checksum' => $data->checksum,
                'status' => 'verified',
                'uploaded_at' => now(),
            ]);
        }

        // Check if complete
        if ($upload->getCompletedChunksCount() === $upload->total_chunks) {
            $this->assembleFile($upload);
        }

        return $this->queries->getUploadProgress($data->upload_id);
    }

    private function assembleFile(Upload $upload): void
    {
        $chunks = $upload->chunks()->where('status', 'verified')
            ->orderBy('chunk_number')->get();

        $finalPath = 'uploads/'.$upload->original_filename;
        $tempPath = storage_path('app/temp_'.$upload->id);

        // Assemble chunks
        $finalFile = fopen($tempPath, 'wb');
        throw_if($finalFile === false, new Exception('Could not open temporary file for writing: '.$tempPath));

        foreach ($chunks as $chunk) {
            $chunkPath = storage_path(sprintf('app/chunks/%s/%s', $upload->id, $chunk->chunk_number));
            $chunkContent = file_get_contents($chunkPath);
            if ($chunkContent === false) {
                fclose($finalFile);
                throw new Exception('Could not read chunk content from: '.$chunkPath);
            }

            if (fwrite($finalFile, $chunkContent) === false) {
                fclose($finalFile);
                throw new Exception('Could not write chunk to temporary file');
            }
        }

        throw_if(fclose($finalFile) === false, new Exception('Could not close temporary file'));

        // Move to final location
        $finalContent = file_get_contents($tempPath);
        throw_if($finalContent === false, new Exception('Could not read final assembled file: '.$tempPath));

        Storage::disk('public')->put($finalPath, $finalContent);

        // Clean up
        unlink($tempPath);
        Storage::disk('local')->deleteDirectory('chunks/'.$upload->id);

        // Update status
        $this->queries->update($upload, [
            'status' => 'completed',
            'upload_path' => $finalPath,
        ]);
    }

    public function getUploadStatus(int $uploadId): array
    {
        return $this->queries->getUploadProgress($uploadId);
    }
}
