<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use App\Models\ShortUrlClick;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UrlShortenerController extends Controller
{
    // ── Public tool page ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = ShortUrl::query()->orderBy('created_at', 'desc');

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('ip_address', $request->ip());
        }

        $myLinks = $query->take(20)->get()->map(fn ($u) => [
            'id'           => $u->id,
            'code'         => $u->code,
            'slug'         => $u->slug,
            'short_url'    => $u->short_url,
            'original_url' => $u->original_url,
            'title'        => $u->title,
            'click_count'  => $u->click_count,
            'is_active'    => $u->is_active,
            'expires_at'   => $u->expires_at?->diffForHumans(),
            'created_at'   => $u->created_at->diffForHumans(),
        ]);

        return Inertia::render('UrlShortener', [
            'canLogin'    => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
            'myLinks'     => $myLinks,
        ]);
    }

    // ── Create short URL ────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url'   => ['required', 'url', 'max:2048'],
            'alias' => ['nullable', 'alpha_dash', 'max:60', 'unique:short_urls,alias'],
            'title' => ['nullable', 'string', 'max:160'],
        ]);

        // Generate unique 6-char code
        do {
            $code = Str::random(6);
        } while (ShortUrl::where('code', $code)->exists());

        $shortUrl = ShortUrl::create([
            'user_id'      => auth()->id(),
            'ip_address'   => $request->ip(),
            'code'         => $code,
            'original_url' => $validated['url'],
            'title'        => $validated['title'] ?? null,
            'alias'        => $validated['alias'] ?? null,
            'is_active'    => true,
        ]);

        return response()->json([
            'success'      => true,
            'id'           => $shortUrl->id,
            'short_url'    => $shortUrl->short_url,
            'code'         => $shortUrl->code,
            'slug'         => $shortUrl->slug,
            'original_url' => $shortUrl->original_url,
            'title'        => $shortUrl->title,
            'click_count'  => 0,
            'is_active'    => true,
            'expires_at'   => null,
            'created_at'   => $shortUrl->created_at->diffForHumans(),
        ]);
    }

    // ── Redirect + record click ─────────────────────────────────────────────────

    public function redirect(Request $request, string $code)
    {
        // Try alias first, then code
        $shortUrl = ShortUrl::where('alias', $code)
            ->orWhere('code', $code)
            ->first();

        if (! $shortUrl || ! $shortUrl->is_active || $shortUrl->isExpired()) {
            abort(404);
        }

        // Detect device type from User-Agent
        $ua         = $request->userAgent() ?? '';
        $deviceType = 'desktop';
        if (preg_match('/bot|crawl|slurp|spider/i', $ua)) {
            $deviceType = 'bot';
        } elseif (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            $deviceType = str_contains(strtolower($ua), 'ipad') || str_contains(strtolower($ua), 'tablet') ? 'tablet' : 'mobile';
        }

        // Record click asynchronously (don't block redirect)
        ShortUrlClick::create([
            'short_url_id' => $shortUrl->id,
            'ip_address'   => $request->ip(),
            'referrer'     => substr($request->header('referer') ?? '', 0, 512),
            'user_agent'   => substr($ua, 0, 512),
            'device_type'  => $deviceType,
            'clicked_at'   => now(),
        ]);

        // Increment denormalized counter
        $shortUrl->increment('click_count');

        return redirect()->away($shortUrl->original_url, 301);
    }

    // ── Delete own link ─────────────────────────────────────────────────────────

    public function destroy(Request $request, ShortUrl $shortUrl)
    {
        // Only owner (matched by IP for guests) or admin can delete
        $isOwner = $shortUrl->user_id !== null
            ? $shortUrl->user_id === auth()->id()
            : $shortUrl->ip_address === $request->ip();

        if (! $isOwner && ! auth()->user()?->is_admin) {
            abort(403, 'You do not have permission to delete this link.');
        }

        $shortUrl->delete();

        return response()->noContent(); // 204 — clean for axios
    }
}
