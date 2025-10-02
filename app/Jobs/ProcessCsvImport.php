<?php

namespace App\Jobs;

use App\Domains\Products\Models\BulkImportResult;
use App\Domains\Products\Services\ProductImportService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    // 5 minutes timeout
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public string $originalFilename,
        public int $importResultId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProductImportService $importService): void
    {
        Log::info('Starting CSV import job', [
            'file_path' => $this->filePath,
            'filename' => $this->originalFilename,
            'import_id' => $this->importResultId,
        ]);

        try {
            // Process the import
            $importService->importFromCsv($this->filePath, $this->originalFilename, $this->importResultId);

            Log::info('CSV import job completed successfully', [
                'import_id' => $this->importResultId,
            ]);

        } catch (Exception $exception) {
            Log::error('CSV import job failed', [
                'import_id' => $this->importResultId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Update the import record to failed status
            $importResult = BulkImportResult::query()->find($this->importResultId);
            if ($importResult) {
                $importResult->update([
                    'status' => 'failed',
                    'errors' => [[
                        'message' => $exception->getMessage(),
                    ]],
                    'completed_at' => now(),
                ]);
            }

            throw $exception;
        }
    }
}
