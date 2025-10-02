<?php

namespace App\Domains\Products\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ChunkUploadData extends Data
{
    public function __construct(
        public int $upload_id,
        public int $chunk_number,
        public string $checksum,
        public UploadedFile $chunk_data,
    ) {}

    public static function rules(): array
    {
        return [
            'upload_id' => ['required', 'integer', 'exists:uploads,id'],
            'chunk_number' => ['required', 'integer', 'min:0'],
            'checksum' => ['required', 'string', 'size:32'],
            'chunk_data' => ['required', 'file', 'max:2048'],
        ];
    }
}
