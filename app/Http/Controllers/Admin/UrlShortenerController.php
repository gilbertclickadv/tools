<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortUrl;
use App\Models\ShortUrlClick;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UrlShortenerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $links = ShortUrl::with('user')
            ->when($search, fn ($q) => $q
                ->where('original_url', 'ilike', "%{$search}%")
                ->orWhere('code', 'ilike', "%{$search}%")
                ->orWhere('alias', 'ilike', "%{$search}%")
                ->orWhere('title', 'ilike', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->through(fn ($u) => [
                'id'           => $u->id,
                'code'         => $u->code,
                'slug'         => $u->slug,
                'short_url'    => $u->short_url,
                'original_url' => $u->original_url,
                'title'        => $u->title,
                'click_count'  => $u->click_count,
                'is_active'    => $u->is_active,
                'user'         => $u->user ? ['name' => $u->user->name, 'email' => $u->user->email] : null,
                'ip_address'   => $u->ip_address,
                'expires_at'   => $u->expires_at?->toDateString(),
                'created_at'   => $u->created_at->format('Y-m-d H:i'),
            ]);

        // Aggregate stats
        $stats = [
            'totalLinks'  => ShortUrl::count(),
            'totalClicks' => ShortUrl::sum('click_count'),
            'activeLinks' => ShortUrl::where('is_active', true)->count(),
            'todayClicks' => ShortUrlClick::whereDate('clicked_at', today())->count(),
        ];

        // Last 14-day click chart
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date    = now()->subDays($i)->format('Y-m-d');
            $label   = now()->subDays($i)->format('M d');
            $clicks  = ShortUrlClick::whereDate('clicked_at', $date)->count();
            $chartData[] = ['date' => $label, 'clicks' => $clicks];
        }

        return Inertia::render('Admin/UrlShortener', [
            'links'     => $links,
            'stats'     => $stats,
            'chartData' => $chartData,
            'filters'   => ['search' => $search],
        ]);
    }

    public function toggle(Request $request, ShortUrl $shortUrl)
    {
        $shortUrl->update(['is_active' => ! $shortUrl->is_active]);
        return back()->with('status', 'Link status updated.');
    }

    public function destroy(ShortUrl $shortUrl)
    {
        $shortUrl->delete();
        return back()->with('status', 'Link permanently deleted.');
    }
}
