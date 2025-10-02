<?php

namespace App\Domains\Products\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkImportResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'filename',
        'total_rows',
        'imported_rows',
        'updated_rows',
        'invalid_rows',
        'duplicate_rows',
        'errors',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the success rate percentage
     */
    protected function successRate(): Attribute
    {
        return Attribute::make(get: function (): int|float {
            if ($this->total_rows === 0) {
                return 0;
            }

            $successful = $this->imported_rows + $this->updated_rows;

            return round(($successful / $this->total_rows) * 100, 2);
        });
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark import as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
