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
        // Fetch simple metrics, potentially using Redis cache
        $totalImagesProcessed = (int) Redis::get('stats:total_processed') ?: 0;
        $totalStorageSavedBytes = (int) Redis::get('stats:storage_saved_bytes') ?: 0;

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalProcessed' => $totalImagesProcessed,
                'storageSavedBytes' => $totalStorageSavedBytes,
                'redisConnected' => true,
            ]
        ]);
    }
}
