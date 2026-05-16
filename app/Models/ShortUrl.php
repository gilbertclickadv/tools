<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortUrl extends Model
{
    protected $fillable = [
        'user_id', 'ip_address', 'code', 'original_url',
        'title', 'alias', 'is_active', 'expires_at', 'click_count',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShortUrlClick::class);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /** The public redirect path component (alias preferred over code). */
    public function getSlugAttribute(): string
    {
        return $this->alias ?: $this->code;
    }

    /** Full short URL using the app domain. */
    public function getShortUrlAttribute(): string
    {
        return rtrim(config('app.short_domain', config('app.url')), '/') . '/s/' . $this->slug;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
