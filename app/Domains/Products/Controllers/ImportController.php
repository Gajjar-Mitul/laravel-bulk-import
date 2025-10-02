<?php

namespace App\Domains\Products\Controllers;

use App\Domains\Products\BulkImportQueries;
use App\Domains\Products\DataObjects\BulkImportData;
use App\Domains\Products\Models\BulkImportResult;
use App\Domains\Products\Services\ProductImportService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        private readonly BulkImportQueries $queries
    ) {}

    public function index(): View
    {
        $recentImports = $this->queries->getRecentImports(10);

        return view('pages.imports.index', [
            'pageTitle' => 'Import Products',
            'recentImports' => $recentImports,
        ]);
    }

    public function status(int $importId)
    {
        $import = $this->queries->findImportById($importId);

        if (! $import instanceof BulkImportResult) {
            return response()->json([
                'error' => 'Import not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'imported_rows' => $import->imported_rows,
                'updated_rows' => $import->updated_rows,
                'invalid_rows' => $import->invalid_rows,
                'duplicate_rows' => $import->duplicate_rows,
                'errors' => $import->errors,
                'started_at' => $import->started_at,
                'completed_at' => $import->completed_at,
                'progress_percentage' => $import->total_rows > 0
                    ? round(($import->imported_rows + $import->updated_rows + $import->invalid_rows + $import->duplicate_rows) / $import->total_rows * 100, 2)
                    : 0,
            ],
        ]);
    }

    public function import(BulkImportData $data)
    {
        $productImportService = resolve(ProductImportService::class);

        // For files larger than 1MB or with many rows, use async processing
        $fileSize = $data->csv_file->getSize();

        if ($fileSize > 1024 * 1024) { // 1MB threshold
            // Use async processing for large files
            $result = $productImportService->queueImportFromUpload($data);

            return response()->json([
                'success' => true,
                'message' => 'Import queued for processing. You will be notified when complete.',
                'data' => [
                    'import_id' => $result->id,
                    'status' => $result->status,
                    'filename' => $result->filename,
                ],
            ]);
        }

        // Use synchronous processing for small files
        $result = $productImportService->importFromUpload($data);

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $result->id,
                'total_rows' => $result->total_rows,
                'imported_rows' => $result->imported_rows,
                'updated_rows' => $result->updated_rows,
                'invalid_rows' => $result->invalid_rows,
                'duplicate_rows' => $result->duplicate_rows,
                'status' => $result->status,
            ],
        ]);
    }

    public function history(): View
    {
        $imports = $this->queries->getRecentImports(50); // Get more for history page

        return view('pages.imports.history', [
            'pageTitle' => 'Import History',
            'imports' => $imports,
        ]);
    }
}
