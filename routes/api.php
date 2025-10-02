<?php

// routes/api.php

use App\Domains\FileUploads\Controllers\Api\ChunkedUploadController;
use App\Domains\Products\Controllers\BulkImportController;
use App\Domains\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

// Bulk Import Routes
Route::prefix('imports')->group(function (): void {
    // Route::post('/products', [BulkImportController::class, 'importProducts']);
    Route::get('/status/{importId}', [BulkImportController::class, 'getImportStatus']);
    Route::get('/history', [BulkImportController::class, 'getImportHistory']);
});

// Chunked Upload Routes
Route::prefix('uploads')->group(function (): void {
    Route::post('/initialize', [ChunkedUploadController::class, 'initializeUpload']);
    Route::post('/chunk', [ChunkedUploadController::class, 'uploadChunk']);
    Route::get('/status/{uuid}', [ChunkedUploadController::class, 'getUploadStatus']);
    Route::post('/attach-to-product', [ChunkedUploadController::class, 'attachImageToProduct']);
    Route::delete('/{uuid}', [ChunkedUploadController::class, 'cancelUpload']);
});

Route::get('/products', fn () => Product::with(['images', 'primaryImage'])->paginate(15));

Route::get('/products/{sku}', function (string $sku) {
    $product = Product::with(['images', 'primaryImage', 'imageVariants'])
        ->where('sku', $sku)
        ->first();

    if (! $product) {
        return response()->json([
            'message' => 'Product not found',
        ], 404);
    }

    return $product;
});
