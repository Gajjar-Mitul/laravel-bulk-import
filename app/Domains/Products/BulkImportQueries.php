<?php

namespace App\Domains\Products;

use App\Domains\Products\Models\BulkImportResult;
use Illuminate\Database\Eloquent\Collection;

class BulkImportQueries
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createImportResult(array $data): BulkImportResult
    {
        return BulkImportResult::query()->create([
            'import_type' => $data['import_type'] ?? 'products',
            'filename' => $data['filename'],
            'total_rows' => $data['total_rows'] ?? 0,
            'status' => $data['status'] ?? 'processing',
            'started_at' => $data['started_at'] ?? now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateImportResult(BulkImportResult $import, array $data): bool
    {
        return $import->update($data);
    }

    public function getRecentImports(int $limit = 10): Collection
    {
        return BulkImportResult::query()->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function findImportById(int $id): ?BulkImportResult
    {
        return BulkImportResult::query()->find($id);
    }
}
