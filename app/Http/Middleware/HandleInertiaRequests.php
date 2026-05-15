<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'appSettings' => [
                'maxUploadMb' => (int) (Redis::get('settings:max_upload_mb') ?: 20),
                'defaultQuality' => (int) (Redis::get('settings:default_quality') ?: 80),
                'guestRetentionHours' => (int) (Redis::get('settings:guest_retention_hours') ?: 6),
                'authRetentionDays' => (int) (Redis::get('settings:auth_retention_days') ?: 7),
            ],
        ];
    }
}
