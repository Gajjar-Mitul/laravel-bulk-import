<?php

namespace App\Domains\Products\DataObjects;

use Spatie\LaravelData\Data;

class ImageUploadInitData extends Data
{
    public function __construct(
        public string $filename,
        public int $total_size,
        public int $total_chunks,
        public string $checksum,
        public ?int $product_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'total_size' => ['required', 'integer', 'min:1', 'max:104857600'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:1000'],
            'checksum' => ['required', 'string', 'size:32'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
