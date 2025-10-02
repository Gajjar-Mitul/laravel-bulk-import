<?php

namespace App\Domains\Products\Controllers;

use App\Domains\Products\DataObjects\BulkImportData;
use App\Domains\Products\Models\BulkImportResult;
use App\Domains\Products\Services\ProductImportService;
use App\Http\Controllers\Controller;
use Exception;

class BulkImportController extends Controller
{
    public function __construct(
        private readonly ProductImportService $importService
    ) {}

    public function importProducts(BulkImportData $data)
    {
        try {
            // $data->csv_file is the uploaded file from the request, not a temp path
            $result = $this->importService->importFromUpload($data);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => [
                    'total_rows' => $result->total_rows,
                    'imported_rows' => $result->imported_rows,
                    'updated_rows' => $result->updated_rows,
                    'invalid_rows' => $result->invalid_rows,
                    'success_rate' => $result->total_rows > 0
                        ? round((($result->imported_rows + $result->updated_rows) / $result->total_rows) * 100, 2).'%'
                        : '0%',
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getImportStatus($importId)
    {
        try {
            /** @var BulkImportResult|null $import */
            $import = BulkImportResult::query()->find($importId);

            if (! $import instanceof BulkImportResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $import->id,
                    'status' => $import->status,
                    'total_rows' => $import->total_rows,
                    'imported_rows' => $import->imported_rows,
                    'updated_rows' => $import->updated_rows,
                    'invalid_rows' => $import->invalid_rows,
                    'success_rate' => $import->total_rows > 0
                        ? round((($import->imported_rows + $import->updated_rows) / $import->total_rows) * 100, 2).'%'
                        : '0%',
                    'errors' => $import->errors ?? [],
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import status',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
