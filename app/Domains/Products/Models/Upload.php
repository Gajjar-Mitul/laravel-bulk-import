<?php

namespace App\Domains\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'filename', 'original_filename', 'total_size', 'total_chunks',
        'checksum', 'status', 'upload_path',
    ];

    /**
     * @return HasMany<UploadChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(UploadChunk::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getCompletedChunksCount(): int
    {
        return $this->chunks()->where('status', 'verified')->count();
    }

    protected function casts(): array
    {
        return [
            'total_size' => 'integer',
            'total_chunks' => 'integer',
        ];
    }
}
