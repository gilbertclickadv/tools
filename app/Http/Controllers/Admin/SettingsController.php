<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redis;

class SettingsController extends Controller
{
    /**
     * Display admin configuration settings.
     */
    public function index()
    {
        $maxUploadSizeMb = (int) (Redis::get('settings:max_upload_mb') ?: 20);
        $defaultQuality = (int) (Redis::get('settings:default_quality') ?: 80);
        $guestRetentionHours = (int) (Redis::get('settings:guest_retention_hours') ?: 6);
        $authRetentionDays = (int) (Redis::get('settings:auth_retention_days') ?: 7);
        $guestDailyLimit = (int) (Redis::get('settings:guest_daily_limit') ?: 15);

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'maxUploadSizeMb' => $maxUploadSizeMb,
                'defaultQuality' => $defaultQuality,
                'guestRetentionHours' => $guestRetentionHours,
                'authRetentionDays' => $authRetentionDays,
                'guestDailyLimit' => $guestDailyLimit,
            ],
            'status' => session('status'),
        ]);
    }

    /**
     * Update configuration settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'maxUploadSizeMb' => ['required', 'integer', 'min:1', 'max:100'],
            'defaultQuality' => ['required', 'integer', 'min:10', 'max:100'],
            'guestRetentionHours' => ['required', 'integer', 'min:1', 'max:72'],
            'authRetentionDays' => ['required', 'integer', 'min:1', 'max:365'],
            'guestDailyLimit' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        Redis::set('settings:max_upload_mb', $validated['maxUploadSizeMb']);
        Redis::set('settings:default_quality', $validated['defaultQuality']);
        Redis::set('settings:guest_retention_hours', $validated['guestRetentionHours']);
        Redis::set('settings:auth_retention_days', $validated['authRetentionDays']);
        Redis::set('settings:guest_daily_limit', $validated['guestDailyLimit']);

        return back()->with('status', 'Settings updated successfully.');
    }
}
