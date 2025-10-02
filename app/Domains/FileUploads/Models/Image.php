<?php

namespace App\Domains\FileUploads\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $upload_id
 * @property string|null $imageable_type
 * @property int|null $imageable_id
 * @property bool $is_primary
 * @property string $variant
 * @property string $storage_path
 * @property int|null $width
 * @property int|null $height
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'imageable_type',
        'imageable_id',
        'is_primary',
        'variant',
        'storage_path',
        'width',
        'height',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the parent imageable model (product, user, etc.)
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the upload record
     *
     * @return BelongsTo<Upload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Get the full URL of the image
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => Storage::url($this->storage_path));
    }

    /**
     * Get file size in human readable format
     */
    protected function humanFileSize(): Attribute
    {
        return Attribute::make(get: function (): string {
            $bytes = $this->file_size ?? 0;
            $units = ['B', 'KB', 'MB', 'GB'];
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }

            return round((float) $bytes, 2).' '.$units[$i];
        });
    }

    /**
     * Scope for primary images
     */
    protected function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for specific variant
     */
    protected function scopeVariant($query, string $variant)
    {
        return $query->where('variant', $variant);
    }

    /**
     * Scope for original images
     */
    protected function scopeOriginal($query)
    {
        return $query->where('variant', 'original');
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
