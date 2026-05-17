<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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

        $cityDbPath = storage_path('app/maxmind/GeoLite2-City.mmdb');
        $asnDbPath = storage_path('app/maxmind/GeoLite2-ASN.mmdb');

        $licenseKey = env('MAXMIND_LICENSE_KEY');
        $accountId = env('MAXMIND_ACCOUNT_ID');

        $maxmind = [
            'hasKeys' => !empty($licenseKey) && !empty($accountId),
            'licenseKeySnippet' => $licenseKey ? substr($licenseKey, 0, 6) . '...' : null,
            'cityDb' => [
                'exists' => file_exists($cityDbPath),
                'size' => file_exists($cityDbPath) ? round(filesize($cityDbPath) / 1024 / 1024, 2) . ' MB' : null,
                'updated_at' => file_exists($cityDbPath) ? date('Y-m-d H:i:s', filemtime($cityDbPath)) : null,
            ],
            'asnDb' => [
                'exists' => file_exists($asnDbPath),
                'size' => file_exists($asnDbPath) ? round(filesize($asnDbPath) / 1024 / 1024, 2) . ' MB' : null,
                'updated_at' => file_exists($asnDbPath) ? date('Y-m-d H:i:s', filemtime($asnDbPath)) : null,
            ],
        ];

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'maxUploadSizeMb' => $maxUploadSizeMb,
                'defaultQuality' => $defaultQuality,
                'guestRetentionHours' => $guestRetentionHours,
                'authRetentionDays' => $authRetentionDays,
                'guestDailyLimit' => $guestDailyLimit,
            ],
            'maxmind' => $maxmind,
            'status' => session('status'),
            'error' => session('error'),
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

    /**
     * Pull/Download the GeoLite2-City and GeoLite2-ASN databases from MaxMind.
     */
    public function updateMaxmind(Request $request)
    {
        $licenseKey = env('MAXMIND_LICENSE_KEY');
        $accountId = env('MAXMIND_ACCOUNT_ID');

        if (empty($licenseKey) || empty($accountId)) {
            return back()->with('error', 'MaxMind credentials (MAXMIND_LICENSE_KEY / MAXMIND_ACCOUNT_ID) are missing from .env.');
        }

        $tmpDir = storage_path('app/maxmind/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $editions = ['GeoLite2-City', 'GeoLite2-ASN'];
        $successCount = 0;

        foreach ($editions as $edition) {
            try {
                $downloadUrl = "https://download.maxmind.com/app/geoip_download?edition_id={$edition}&license_key={$licenseKey}&suffix=tar.gz";
                $tarPath = $tmpDir . "/{$edition}.tar.gz";
                $extractDir = $tmpDir . "/{$edition}_extracted";

                if (!is_dir($extractDir)) {
                    mkdir($extractDir, 0755, true);
                }

                // 1. Download database archive
                Log::info("Downloading MaxMind database: {$edition}");
                $response = Http::timeout(60)->sink($tarPath)->get($downloadUrl);

                if (!$response->successful()) {
                    Log::error("Failed to download MaxMind database {$edition}: HTTP " . $response->status());
                    continue;
                }

                // 2. Extract database using tar (Linux native, reliable, low memory)
                Log::info("Extracting MaxMind database archive: {$edition}");
                shell_exec("tar -xzf " . escapeshellarg($tarPath) . " -C " . escapeshellarg($extractDir));

                // 3. Locate the .mmdb file recursively in extracted directory
                $mmdbFoundPath = null;
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($extractDir)
                );
                foreach ($files as $file) {
                    if ($file->isFile() && $file->getExtension() === 'mmdb') {
                        $mmdbFoundPath = $file->getPathname();
                        break;
                    }
                }

                if ($mmdbFoundPath) {
                    $destPath = storage_path("app/maxmind/{$edition}.mmdb");
                    if (!is_dir(dirname($destPath))) {
                        mkdir(dirname($destPath), 0755, true);
                    }
                    copy($mmdbFoundPath, $destPath);
                    $successCount++;
                    Log::info("Successfully installed MaxMind database: {$edition}.mmdb");
                } else {
                    Log::error("MMDB file not found in extracted archive for {$edition}");
                }
            } catch (\Exception $e) {
                Log::error("Error processing MaxMind database {$edition}: " . $e->getMessage());
            }
        }

        // Cleanup temporary directory
        if (is_dir($tmpDir)) {
            shell_exec("rm -rf " . escapeshellarg($tmpDir));
        }

        if ($successCount === count($editions)) {
            return back()->with('status', 'MaxMind City and ASN databases updated successfully.');
        } elseif ($successCount > 0) {
            return back()->with('status', 'MaxMind databases partially updated. Check server logs.');
        } else {
            return back()->with('error', 'Failed to update MaxMind databases. Check server logs.');
        }
    }
}
