<?php

namespace App\Domains\Products\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class BulkImportData extends Data
{
    public function __construct(
        public UploadedFile $csv_file,        // Changed from csvfile to csv_file
        public ?bool $update_existing = true, // Changed from updateexisting
        public ?bool $skip_invalid = true,    // Changed from skipinvalid
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        // Convert string true/false to actual boolean
        foreach (['update_existing', 'skip_invalid'] as $key) {
            if (isset($properties[$key]) && is_string($properties[$key])) {
                $properties[$key] = filter_var($properties[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $properties;
    }
}
