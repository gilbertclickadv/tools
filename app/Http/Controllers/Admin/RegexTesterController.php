<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegexTesterController extends Controller
{
    /**
     * Display the Regex Tester analytics dashboard in the admin portal.
     */
    public function index()
    {
        $pathFilter = '%tools/regex%';
        
        $stats = [
            'totalHits' => ActivityLog::where('path', 'like', $pathFilter)->count(),
            'todayHits' => ActivityLog::where('path', 'like', $pathFilter)->whereDate('created_at', today())->count(),
            'uniqueIPs' => ActivityLog::where('path', 'like', $pathFilter)->distinct('ip_address')->count(),
            'authVisits' => ActivityLog::where('path', 'like', $pathFilter)->whereNotNull('user_id')->count(),
        ];

        // 14-day traffic chart
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date' => now()->subDays($i)->format('M d'),
                'count' => ActivityLog::where('path', 'like', $pathFilter)->whereDate('created_at', $date)->count(),
            ];
        }

        // Recent users visiting this tool
        $recentVisits = ActivityLog::with('user')
            ->where('path', 'like', $pathFilter)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'ip_address' => $log->ip_address,
                'user' => $log->user ? ['name' => $log->user->name, 'email' => $log->user->email] : null,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->format('M d, H:i'),
            ]);

        return Inertia::render('Admin/RegexTester', [
            'stats' => $stats,
            'chartData' => $chartData,
            'recentVisits' => $recentVisits,
        ]);
    }
}
