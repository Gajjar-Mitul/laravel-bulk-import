<?php

namespace App\Domains\Products\Models;

use App\Domains\FileUploads\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'category',
        'is_active',
        'metadata',
    ];

    /**
     * Get all images for the product
     *
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the primary image for the product
     *
     * @return MorphMany<Image, $this>
     */
    public function primaryImage(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('is_primary', true)
            ->where('variant', 'original');
    }

    /**
     * Get all image variants for the product
     *
     * @return MorphMany<Image, $this>
     */
    public function imageVariants(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('is_primary', true);
    }

    /**
     * Scope to find by SKU
     */
    protected function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    /**
     * Scope for active products
     */
    protected function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
