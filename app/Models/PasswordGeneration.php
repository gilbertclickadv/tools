<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordGeneration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'length',
        'use_uppercase', 'use_lowercase', 'use_numbers', 'use_symbols',
        'generated_at',
    ];

    protected $casts = [
        'use_uppercase' => 'boolean',
        'use_lowercase' => 'boolean',
        'use_numbers'   => 'boolean',
        'use_symbols'   => 'boolean',
        'generated_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
