<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'ip_address',
    'profile_type',
    'summary_payload',
    'foreground_color',
    'background_color',
    'matrix_size',
    'redundancy_level',
])]
class StoredQrCode extends Model
{
    /**
     * Get the user that owns the stored QR code record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
