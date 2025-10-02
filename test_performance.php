<?php

use App\Domains\Products\BulkImportQueries;
use App\Domains\Products\DataObjects\BulkImportData;
use App\Domains\Products\ProductQueries;
use App\Domains\Products\Services\ProductImportService;
use Illuminate\Http\UploadedFile;

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing import performance...\n";

// Create service
$productQueries = new ProductQueries;
$importQueries = new BulkImportQueries;
$service = new ProductImportService($productQueries, $importQueries);

// Create fake uploaded file
$csvFile = new UploadedFile(
    'performance-test.csv',
    'performance-test.csv',
    'text/csv',
    null,
    true
);

$data = BulkImportData::from([
    'csv_file' => $csvFile,
    'update_existing' => true,
    'skip_invalid' => true,
]);

$startTime = microtime(true);

try {
    $result = $service->importFromUpload($data);
    $endTime = microtime(true);

    $duration = $endTime - $startTime;

    echo 'Import completed in: '.round($duration, 2)." seconds\n";
    echo 'Total rows: '.$result->total_rows."\n";
    echo 'Imported: '.$result->imported_rows."\n";
    echo 'Updated: '.$result->updated_rows."\n";
    echo 'Invalid: '.$result->invalid_rows."\n";
    echo 'Duplicates: '.$result->duplicate_rows."\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
