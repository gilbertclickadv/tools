<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedUuid extends Model
{
    protected $fillable = ['user_id', 'ip_address', 'uuid', 'version', 'label'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
