<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpLookup extends Model
{
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'ip_address',
        'searched_ip',
        'country_code',
        'country_name',
        'city_name',
        'asn',
        'isp'
    ];

    /**
     * Get the user that made the lookup.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
