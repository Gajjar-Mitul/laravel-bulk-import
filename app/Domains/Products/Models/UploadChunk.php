<?php

namespace App\Domains\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadChunk extends Model
{
    protected $fillable = [
        'upload_id', 'chunk_number', 'size', 'checksum', 'status', 'uploaded_at',
    ];

    /**
     * @return BelongsTo<Upload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'verified',
            'uploaded_at' => now(),
        ]);
    }

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'size' => 'integer',
            'chunk_number' => 'integer',
        ];
    }
}
