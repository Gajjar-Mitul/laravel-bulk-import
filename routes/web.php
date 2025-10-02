<?php

use App\Domains\FileUploads\Controllers\UploadController;
use App\Domains\Products\Controllers\ImportController;
use App\Domains\Products\Controllers\ProductController;
use App\Domains\Products\Models\BulkImportResult;
use App\Domains\Products\Models\Product;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): View|Factory => view('pages.dashboard.index'))->name('dashboard');

// Products Routes
Route::prefix('products')->name('products.')->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
});

// Import Routes
Route::prefix('imports')->name('imports.')->group(function (): void {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('/products', [ImportController::class, 'import'])->name('products.store');
    Route::get('/status/{importId}', [ImportController::class, 'status'])->name('status');
    Route::get('/history', [ImportController::class, 'history'])->name('history');
});

// Upload Routes
Route::prefix('uploads')->name('uploads.')->group(function (): void {
    Route::get('/', [UploadController::class, 'index'])->name('index');
    Route::get('/gallery', [UploadController::class, 'gallery'])->name('gallery');
    Route::get('/image/{uuid}/{variant?}', [UploadController::class, 'serveImage'])->name('image');
});

// Dashboard API Routes
Route::prefix('api')->name('api.')->group(function (): void {
    Route::get('/dashboard/stats', fn () => response()->json([
        'total_products' => Product::query()->count(),
        'successful_imports' => BulkImportResult::query()->where('status', 'completed')->count(),
        'total_images' => 0, // TODO: Implement when image feature is ready
        'pending_uploads' => 0, // TODO: Implement when upload feature is ready
        'recent_imports' => BulkImportResult::with([])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($import): array => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'success_rate' => $import->total_rows > 0
                    ? round(($import->imported_rows + $import->updated_rows) / $import->total_rows * 100, 1)
                    : 0,
                'date' => $import->created_at->format('M j, Y g:i A'),
            ]),
    ]))->name('dashboard.stats');
});
