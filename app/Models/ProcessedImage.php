<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'ip_address',
    'original_name',
    'filename',
    'disk_path',
    'format',
    'size_bytes',
    'expires_at',
])]
class ProcessedImage extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the processed image record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
