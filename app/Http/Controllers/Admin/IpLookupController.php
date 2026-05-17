<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IpLookupController extends Controller
{
    /**
     * Display the administrative IP Lookup analytics dashboard.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Main paginated query log
        $records = IpLookup::with('user')
            ->when($search, function ($q) use ($search) {
                $q->where('searched_ip', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('country_name', 'like', "%{$search}%")
                  ->orWhere('isp', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->through(fn ($r) => [
                'id' => $r->id,
                'username' => $r->user ? $r->user->name : 'Guest',
                'ip_address' => $r->ip_address,
                'searched_ip' => $r->searched_ip,
                'country_name' => $r->country_name,
                'country_code' => $r->country_code,
                'isp' => $r->isp,
                'created_at' => $r->created_at->diffForHumans(),
            ]);

        // Key stats
        $total = IpLookup::count();
        $today = IpLookup::whereDate('created_at', today())->count();
        $uniqueTargets = IpLookup::distinct('searched_ip')->count('searched_ip');
        
        // Local vs external API resolved
        $cityDb = storage_path('app/maxmind/GeoLite2-City.mmdb');
        $asnDb = storage_path('app/maxmind/GeoLite2-ASN.mmdb');
        
        $hasMaxmind = file_exists($cityDb) && file_exists($asnDb);

        $stats = [
            'total' => $total,
            'today' => $today,
            'unique_targets' => $uniqueTargets,
            'has_maxmind' => $hasMaxmind,
        ];

        // 14-day search history activity chart data
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date' => now()->subDays($i)->format('M d'),
                'count' => IpLookup::whereDate('created_at', $date)->count(),
            ];
        }

        // Top countries queried
        $topCountries = IpLookup::select('country_name', 'country_code', DB::raw('count(*) as count'))
            ->whereNotNull('country_name')
            ->groupBy('country_name', 'country_code')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'country' => $c->country_name,
                'code' => $c->country_code,
                'count' => $c->count,
            ]);

        return Inertia::render('Admin/IpLookup', [
            'records' => $records,
            'stats' => $stats,
            'chartData' => $chartData,
            'topCountries' => $topCountries,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Delete a specific search query log record.
     */
    public function destroy(IpLookup $ipLookup)
    {
        $ipLookup->delete();
        return back()->with('status', 'IP search log record deleted.');
    }
}
