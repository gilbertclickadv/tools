<?php

namespace App\Http\Controllers;

use App\Models\ProcessedImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackgroundRemoverController extends Controller
{
    /**
     * Display the background remover tool page with history.
     */
    public function index()
    {
        $user = Auth::guard('web')->user();
        $query = ProcessedImage::query();

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('ip_address', request()->ip());
        }

        // Only show images processed by background remover (prefixed with bg_)
        $history = $query->where('disk_path', 'like', 'processed/bg_%')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get()
            ->map(function ($img) {
                return [
                    'id'            => $img->id,
                    'original_name' => $img->original_name,
                    'format'        => strtoupper($img->format),
                    'size_bytes'    => $img->size_bytes,
                    'expires_at'    => $img->expires_at ? $img->expires_at->diffForHumans() : null,
                    'download_url'  => route('image.download', ['path' => $img->disk_path]),
                    'output_url'    => '/storage/' . $img->disk_path,
                    'created_at'    => $img->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('BackgroundRemover', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'initialHistory' => $history,
        ]);
    }

    /**
     * Process background removal using RMBG-1.4 python script.
     */
    public function process(Request $request)
    {
        $maxUploadSizeMb = (int) (Redis::get('settings:max_upload_mb') ?: 20);
        $maxUploadSizeKb = $maxUploadSizeMb * 1024;

        $request->validate([
            'image' => ['required', 'image', "max:{$maxUploadSizeKb}"],
            'mode'  => ['nullable', 'string', 'in:smooth,sharp,text'],
        ]);

        try {
            $user = Auth::guard('web')->user();
            $ipAddress = $request->ip();
            $mode = $request->input('mode', 'smooth');

            // Enforce Quotas for Background Remover (1 for guest, 2 for logged-in, unlimited for admin)
            if (!$user || ($user && !$user->is_admin)) {
                if (!$user) {
                    // Guest user: Limit is 1 per day
                    $todayCount = ProcessedImage::where('ip_address', $ipAddress)
                        ->where('disk_path', 'like', 'processed/bg_%')
                        ->where('created_at', '>=', now()->startOfDay())
                        ->count();

                    if ($todayCount >= 1) {
                        return response()->json([
                            'success' => false,
                            'message' => "Daily limit of 1 AI background removal reached for guest sessions. Please sign in to unlock higher limits."
                        ], 429);
                    }
                } else {
                    // Logged in user (non-admin): Limit is 2 per day
                    $todayCount = ProcessedImage::where('user_id', $user->id)
                        ->where('disk_path', 'like', 'processed/bg_%')
                        ->where('created_at', '>=', now()->startOfDay())
                        ->count();

                    if ($todayCount >= 2) {
                        return response()->json([
                            'success' => false,
                            'message' => "Daily limit of 2 AI background removals reached for your account. Unlimited access is available for administrators."
                        ], 429);
                    }
                }
            }

            $file = $request->file('image');
            $originalSizeBytes = $file->getSize();
            $originalName = $file->getClientOriginalName();
            $originalExtension = $file->getClientOriginalExtension();

            // Path to temporary uploaded file
            $tempInputPath = $file->getRealPath();

            // Setup final output name and disk path
            $filename = 'bg_' . Str::random(40) . '.png';
            $diskPath = 'processed/' . $filename;
            
            // Temporary local output path
            $tempOutputPath = storage_path('app/temp_' . $filename);
            
            // Cache directory path for Hugging Face models inside Laravel storage
            $cacheDir = storage_path('app/models');
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            // Path to virtualenv python and inference script
            $pythonPath = base_path('.venv/bin/python3');
            $scriptPath = base_path('app/Scripts/remove_background.py');

            // Dispatch Symfony Process
            $process = new Process([
                $pythonPath,
                $scriptPath,
                $tempInputPath,
                $tempOutputPath,
                $cacheDir,
                $mode
            ]);

            // Set process timeout to 180 seconds to allow model download if running for first time
            $process->setTimeout(180);
            $process->run();

            // Check if process ran successfully
            if (!$process->isSuccessful() || !file_exists($tempOutputPath)) {
                throw new ProcessFailedException($process);
            }

            // Read the generated PNG data
            $binaryData = file_get_contents($tempOutputPath);
            $processedSizeBytes = strlen($binaryData);
            
            // Delete temp output file
            @unlink($tempOutputPath);

            // Persist final processed PNG onto public storage disk
            Storage::disk('public')->put($diskPath, $binaryData);
            
            $base64DataUrl = 'data:image/png;base64,' . base64_encode($binaryData);

            // Determine Expiration Lifecycle from settings
            if ($user) {
                $authRetentionDays = (int) (Redis::get('settings:auth_retention_days') ?: 7);
                $expiresAt = now()->addDays($authRetentionDays);
            } else {
                $guestRetentionHours = (int) (Redis::get('settings:guest_retention_hours') ?: 6);
                $expiresAt = now()->addHours($guestRetentionHours);
            }

            // Commit DB Record
            $record = ProcessedImage::create([
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
                'original_name' => pathinfo($originalName, PATHINFO_FILENAME) . '_no_bg.png',
                'filename' => $filename,
                'disk_path' => $diskPath,
                'format' => 'png',
                'size_bytes' => $processedSizeBytes,
                'expires_at' => $expiresAt,
            ]);

            $downloadUrl = route('image.download', ['path' => $diskPath]);
            $savedBytes = max(0, $originalSizeBytes - $processedSizeBytes);
            $reductionPercentage = $originalSizeBytes > 0 ? round(($savedBytes / $originalSizeBytes) * 100, 1) : 0;

            // Increment Global Caching Counters in Redis asynchronously/safely
            try {
                Redis::incr('stats:total_processed');
                if ($savedBytes > 0) {
                    Redis::incrby('stats:storage_saved_bytes', $savedBytes);
                }
            } catch (\Exception $e) {
                // silently proceed if redis metrics increment hits transient issues
            }

            return response()->json([
                'success' => true,
                'dataUrl' => $base64DataUrl,
                'downloadUrl' => $downloadUrl,
                'originalSizeBytes' => $originalSizeBytes,
                'processedSizeBytes' => $processedSizeBytes,
                'savedBytes' => $savedBytes,
                'reductionPercentage' => $reductionPercentage,
                'format' => 'png',
                'historyItem' => [
                    'id' => $record->id,
                    'original_name' => $record->original_name,
                    'format' => 'PNG',
                    'size_bytes' => $processedSizeBytes,
                    'expires_at' => $expiresAt->diffForHumans(),
                    'download_url' => $downloadUrl,
                    'output_url' => '/storage/' . $diskPath,
                    'created_at' => 'Just now',
                ]
            ]);

        } catch (\Exception $e) {
            // Cleanup temp output file if it exists
            if (isset($tempOutputPath) && file_exists($tempOutputPath)) {
                @unlink($tempOutputPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Background removal error: ' . $e->getMessage()
            ], 500);
        }
    }
}
