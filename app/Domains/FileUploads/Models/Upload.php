<?php

namespace App\Domains\FileUploads\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Override;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'original_filename',
        'mime_type',
        'total_size',
        'total_chunks',
        'uploaded_chunks',
        'checksum',
        'status',
        'storage_path',
        'chunk_info',
        'completed_at',
    ];

    /**
     * Boot the model
     */
    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($upload): void {
            if (empty($upload->uuid)) {
                $upload->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get all images generated from this upload
     *
     * @return HasMany<Image, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Check if upload is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->uploaded_chunks === $this->total_chunks;
    }

    /**
     * Check if upload is in progress
     */
    public function isUploading(): bool
    {
        return $this->status === 'uploading';
    }

    /**
     * Get upload progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        // Get chunk_info as array, handling different possible types
        $chunkInfo = $this->getChunkInfoArray();
        $completedChunks = array_filter($chunkInfo, fn ($completed): bool => $completed);

        return (int) round((count($completedChunks) / $this->total_chunks) * 100);
    }

    /**
     * Get chunk_info as array regardless of how it's stored
     *
     * @return array<int, bool>
     */
    private function getChunkInfoArray(): array
    {
        $raw = $this->attributes['chunk_info'] ?? null;

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Mark chunk as uploaded
     */
    public function markChunkUploaded(int $chunkIndex): void
    {
        $chunkInfo = $this->getChunkInfoArray();
        $chunkInfo[$chunkIndex] = true;

        $completedCount = count(array_filter($chunkInfo, fn ($completed): bool => $completed));

        $this->update([
            'chunk_info' => $chunkInfo,
            'uploaded_chunks' => $completedCount,
            'status' => $completedCount === $this->total_chunks ? 'completed' : 'uploading',
            'completed_at' => $completedCount === $this->total_chunks ? now() : null,
        ]);
    }

    /**
     * Check if specific chunk is uploaded
     */
    public function isChunkUploaded(int $chunkIndex): bool
    {
        $chunkInfo = $this->getChunkInfoArray();

        return ($chunkInfo[$chunkIndex] ?? false) === true;
    }

    /**
     * Scope to find by UUID
     */
    protected function scopeByUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    protected function casts(): array
    {
        return [
            'chunk_info' => 'array',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
