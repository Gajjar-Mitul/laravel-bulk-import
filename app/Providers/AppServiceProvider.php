<?php

namespace App\Providers;

use App\Domains\FileUploads\Services\ChunkedUploadService;
use App\Domains\FileUploads\Services\ImageProcessingService;
use App\Domains\Products\Services\ProductImportService;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(ProductImportService::class);
        $this->app->singleton(ChunkedUploadService::class);
        $this->app->singleton(ImageProcessingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
