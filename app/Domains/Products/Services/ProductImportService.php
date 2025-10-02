<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\BulkImportQueries;
use App\Domains\Products\DataObjects\BulkImportData;
use App\Domains\Products\DataObjects\ProductData;
use App\Domains\Products\Models\BulkImportResult;
use App\Domains\Products\Models\Product;
use Exception;
use League\Csv\Reader;
use League\Csv\Statement;

class ProductImportService
{
    public function __construct(
        private readonly BulkImportQueries $importQueries,
        private readonly ProductService $productService
    ) {}

    public function importFromFile(BulkImportData $data): BulkImportResult
    {
        return $this->importFromUpload($data);
    }

    public function importFromUpload(BulkImportData $data): BulkImportResult
    {
        // Create import record
        $importResult = $this->importQueries->createImportResult([
            'import_type' => 'products',
            'filename' => $data->csv_file->getClientOriginalName(),
            'total_rows' => 0,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Read CSV content directly from uploaded file
            $csvContent = $data->csv_file->getContent();
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0);
            $records = Statement::create()->process($csv);

            $stats = [
                'imported' => 0,
                'updated' => 0,
                'invalid' => 0,
                'duplicate' => 0,
            ];
            $errors = [];
            $totalRows = 0;
            $seenSkus = [];

            foreach ($records as $record) {
                $totalRows++;

                try {
                    // Check for duplicate SKUs within the same import batch
                    if (isset($seenSkus[$record['sku']])) {
                        $stats['duplicate']++;

                        continue;
                    }

                    $seenSkus[$record['sku']] = true;

                    // Create ProductData from CSV row
                    $productData = ProductData::from($record);

                    // Check if product exists
                    $existingProduct = $this->productService->findBySku($productData->sku);

                    if ($existingProduct instanceof Product && ($data->update_existing ?? true)) {
                        $this->productService->update($existingProduct, $productData);
                        $stats['updated']++;
                    } elseif (! $existingProduct instanceof Product) {
                        $this->productService->create($productData);
                        $stats['imported']++;
                    } else {
                        $stats['duplicate']++;
                    }
                } catch (Exception $e) {
                    $stats['invalid']++;
                    $errors[] = [
                        'row' => $totalRows,
                        'message' => $e->getMessage(),
                        'data' => $record,
                    ];
                    throw_unless($data->skip_invalid ?? true, $e);
                }
            }

            // Update import result
            $this->importQueries->updateImportResult($importResult, [
                'total_rows' => $totalRows,
                'imported_rows' => $stats['imported'],
                'updated_rows' => $stats['updated'],
                'invalid_rows' => $stats['invalid'],
                'duplicate_rows' => $stats['duplicate'],
                'errors' => $errors,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $freshResult = $importResult->fresh();
            throw_if($freshResult === null, new Exception('Failed to refresh import result'));

            return $freshResult;

        } catch (Exception $exception) {
            $this->importQueries->updateImportResult($importResult, [
                'status' => 'failed',
                'errors' => [[
                    'message' => $exception->getMessage(),
                ]],
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function processImportFile(string $filePath, string $originalFilename, int $importResultId): void
    {
        // Basic implementation for background processing
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function queueImportFromUpload(BulkImportData $data): BulkImportResult
    {
        return $this->importFromUpload($data);
    }

    public function importFromCsv(string $filePath, string $filename, int $importId): void
    {
        $this->processImportFile($filePath, $filename, $importId);
    }
}
