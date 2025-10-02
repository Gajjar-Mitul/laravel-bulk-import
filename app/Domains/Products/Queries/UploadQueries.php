<?php

namespace App\Domains\Products\Queries;

use App\Domains\Products\Models\Upload;
use App\Domains\Products\Models\UploadChunk;

class UploadQueries
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Upload
    {
        return Upload::query()->create($data);
    }

    public function findById(int $id): ?Upload
    {
        return Upload::with(['chunks', 'productImages'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Upload $upload, array $data): bool
    {
        return $upload->update($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createChunk(array $data): UploadChunk
    {
        return UploadChunk::query()->create($data);
    }

    public function findChunk(int $uploadId, int $chunkNumber): ?UploadChunk
    {
        return UploadChunk::query()->where('upload_id', $uploadId)
            ->where('chunk_number', $chunkNumber)
            ->first();
    }

    public function getUploadProgress(int $uploadId): array
    {
        $upload = $this->findById($uploadId);
        if (! $upload instanceof Upload) {
            return [
                'progress' => 0,
                'completed' => 0,
                'total' => 0,
            ];
        }

        $completed = $upload->getCompletedChunksCount();
        $total = $upload->total_chunks;
        $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return [
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total,
            'status' => $upload->status,
        ];
    }
}
