<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Fetch simple metrics from Redis
        $totalImagesProcessed = (int) Redis::get('stats:total_processed') ?: 0;
        $totalStorageSavedBytes = (int) Redis::get('stats:storage_saved_bytes') ?: 0;

        // Fetch activity metrics for the last 14 days for the graph
        $days = 14;
        $startDate = now()->subDays($days)->startOfDay();

        $activities = \App\Models\ActivityLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_visits, COUNT(DISTINCT ip_address) as unique_visits')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // Fill in missing days with zeros for a smooth graph
        $chartData = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $found = $activities->firstWhere('date', $date);
            
            $chartData[] = [
                'date' => now()->subDays($i)->format('M d'),
                'total' => $found ? $found->total_visits : 0,
                'unique' => $found ? $found->unique_visits : 0,
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalProcessed' => $totalImagesProcessed,
                'storageSavedBytes' => $totalStorageSavedBytes,
                'totalVisits' => \App\Models\ActivityLog::count(),
                'uniqueIPs' => \App\Models\ActivityLog::distinct('ip_address')->count(),
                'redisConnected' => true,
            ],
            'chartData' => $chartData
        ]);
    }
}
