<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'path',
        'method',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
