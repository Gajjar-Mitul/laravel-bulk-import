<?php

namespace App\Domains\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id', 'upload_id', 'variant', 'path',
        'width', 'height', 'size', 'is_primary',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Upload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function getUrl(): string
    {
        return asset('storage/'.$this->path);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
        ];
    }
}
