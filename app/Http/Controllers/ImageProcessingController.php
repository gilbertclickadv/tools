<?php

namespace App\Http\Controllers;

use App\Models\ProcessedImage;
use App\Models\StoredQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageProcessingController extends Controller
{
    /**
     * Process an uploaded image based on requested manipulation options.
     */
    public function process(Request $request)
    {
        $maxUploadSizeMb = (int) (Redis::get('settings:max_upload_mb') ?: 20);
        $maxUploadSizeKb = $maxUploadSizeMb * 1024;

        $request->validate([
            'image' => ['required', 'image', "max:{$maxUploadSizeKb}"], 
            'action' => ['required', 'string', 'in:resize,convert,adjust,crop'],
            // Action specific validation
            'quality' => ['nullable', 'integer', 'min:1', 'max:100'],
            'width' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'height' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'maintainAspectRatio' => ['nullable', 'boolean'],
            'format' => ['nullable', 'string', 'in:jpeg,png,webp,gif,avif,tiff,bmp,ico'],
            'greyscale' => ['nullable', 'boolean'],
            'blur' => ['nullable', 'integer', 'min:0', 'max:100'],
            'brightness' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'crop_width' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'crop_height' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'crop_x' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'crop_y' => ['nullable', 'integer', 'min:0', 'max:5000'],
        ]);

        try {
            $user = Auth::guard('web')->user();
            $ipAddress = $request->ip();

            // Enforce Unauthenticated Quotas (Bypass for Admins)
            if (!$user || ($user && !$user->is_admin)) {
                // If logged in but not admin, we could add a limit here too.
                // For now, let's just ensure guests are limited.
                if (!$user) {
                    $guestDailyLimit = (int) (Redis::get('settings:guest_daily_limit') ?: 15);
                    $todayCount = ProcessedImage::where('ip_address', $ipAddress)
                        ->where('created_at', '>=', now()->startOfDay())
                        ->count();

                    if ($todayCount >= $guestDailyLimit) {
                        return response()->json([
                            'success' => false,
                            'message' => "Daily limit of {$guestDailyLimit} files reached for guest sessions. Please sign in to unlock unlimited premium processing capability."
                        ], 429);
                    }
                }
            }

            $file = $request->file('image');
            $originalSizeBytes = $file->getSize();
            $originalMime = $file->getClientMimeType();
            $originalName = $file->getClientOriginalName();

            // Initialize ImageManager with GD driver
            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $action = $request->input('action');
            $defaultQuality = (int) (Redis::get('settings:default_quality') ?: 80);
            $quality = (int) ($request->input('quality') ?: $defaultQuality);
            $targetFormat = $request->input('format') ?: $this->getExtensionFromMime($originalMime);

            // Apply specific action transformations
            if ($action === 'resize') {
                $width = $request->input('width') ? (int) $request->input('width') : $image->width();
                $height = $request->input('height') ? (int) $request->input('height') : $image->height();
                $maintainAspect = filter_var($request->input('maintainAspectRatio', true), FILTER_VALIDATE_BOOLEAN);

                if ($maintainAspect) {
                    // scale maintains aspect ratio
                    $image->scale(width: $request->input('width') ? $width : null, height: $request->input('height') ? $height : null);
                } else {
                    $image->resize($width, $height);
                }
            } elseif ($action === 'adjust') {
                if (filter_var($request->input('greyscale'), FILTER_VALIDATE_BOOLEAN)) {
                    $image->grayscale();
                }
                if ($request->filled('blur') && (int) $request->input('blur') > 0) {
                    $image->blur((int) $request->input('blur'));
                }
                if ($request->filled('brightness') && (int) $request->input('brightness') !== 0) {
                    $image->brightness((int) $request->input('brightness'));
                }
            } elseif ($action === 'crop') {
                $cropWidth = $request->input('crop_width') ? (int) $request->input('crop_width') : min(300, $image->width());
                $cropHeight = $request->input('crop_height') ? (int) $request->input('crop_height') : min(300, $image->height());
                $cropX = (int) $request->input('crop_x');
                $cropY = (int) $request->input('crop_y');

                // cap boundaries to ensure requested region fits inside actual raw image dimensions
                $cropWidth = min($cropWidth, $image->width() - $cropX);
                $cropHeight = min($cropHeight, $image->height() - $cropY);

                if ($cropWidth > 0 && $cropHeight > 0) {
                    $image->crop($cropWidth, $cropHeight, $cropX, $cropY);
                }
            }

            // Encode to target format
            $encoded = null;
            switch ($targetFormat) {
                case 'png':
                    $encoded = $image->encodeUsingFormat(Format::PNG);
                    $mime = 'image/png';
                    $ext = 'png';
                    break;
                case 'gif':
                    $encoded = $image->encodeUsingFormat(Format::GIF);
                    $mime = 'image/gif';
                    $ext = 'gif';
                    break;
                case 'webp':
                    $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality);
                    $mime = 'image/webp';
                    $ext = 'webp';
                    break;
                case 'avif':
                    $encoded = $image->encodeUsingFormat(Format::AVIF, quality: $quality);
                    $mime = 'image/avif';
                    $ext = 'avif';
                    break;
                case 'bmp':
                    $encoded = $image->encodeUsingFormat(Format::BMP);
                    $mime = 'image/bmp';
                    $ext = 'bmp';
                    break;
                case 'tiff':
                    $encoded = $image->encodeUsingFormat(Format::TIFF);
                    $mime = 'image/tiff';
                    $ext = 'tiff';
                    break;
                case 'ico':
                    // Encode as PNG first (standard for modern ICO)
                    $pngData = $image->encodeUsingFormat(Format::PNG)->toString();
                    $width = $image->width();
                    $height = $image->height();
                    
                    // Simple ICO header for a single PNG image
                    $icoHeader = pack('v3', 0, 1, 1); // Reserved, Type (1), Count (1)
                    $icoEntry = pack('CCCCvvVV', 
                        $width >= 256 ? 0 : $width,
                        $height >= 256 ? 0 : $height,
                        0, // Color count
                        0, // Reserved
                        1, // Planes
                        32, // BPP
                        strlen($pngData),
                        22 // Offset (Header 6 bytes + Entry 16 bytes)
                    );
                    
                    $binaryData = $icoHeader . $icoEntry . $pngData;
                    $mime = 'image/x-icon';
                    $ext = 'ico';
                    
                    // For the PREVIEW (dataUrl), use the PNG data so browsers can render it easily
                    $previewMime = 'image/png';
                    $previewBinary = $pngData;
                    break;
                case 'jpeg':
                default:
                    $image->fillTransparentAreas('ffffff');
                    $encoded = $image->encodeUsingFormat(Format::JPEG, quality: $quality);
                    $mime = 'image/jpeg';
                    $ext = 'jpeg';
                    break;
            }

            if ($targetFormat !== 'ico') {
                $binaryData = $encoded->toString();
            }
            $processedSizeBytes = strlen($binaryData);
            $base64DataUrl = 'data:' . ($previewMime ?? $mime) . ';base64,' . base64_encode($previewBinary ?? $binaryData);

            // Persist onto public physical disk
            $filename = Str::random(40) . '.' . $ext;
            $diskPath = 'processed/' . $filename;
            Storage::disk('public')->put($diskPath, $binaryData);

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
                'original_name' => $originalName,
                'filename' => $filename,
                'disk_path' => $diskPath,
                'format' => $ext,
                'size_bytes' => $processedSizeBytes,
                'expires_at' => $expiresAt,
            ]);

            $downloadUrl = route('image.download', ['path' => $diskPath]);

            // Calculate metrics
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
                'format' => $ext,
                'historyItem' => [
                    'id' => $record->id,
                    'original_name' => $originalName,
                    'format' => strtoupper($ext),
                    'size_bytes' => $processedSizeBytes,
                    'expires_at' => $expiresAt->diffForHumans(),
                    'download_url' => $downloadUrl,
                    'output_url' => Storage::disk('public')->url($diskPath),
                    'created_at' => 'Just now',
                ],
                'dimensions' => [
                    'width' => $image->width(),
                    'height' => $image->height(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image manipulation error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map MIME type string to general format extension.
     */
    private function getExtensionFromMime($mime)
    {
        switch ($mime) {
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            case 'image/jpeg':
            default:
                return 'jpeg';
        }
    }

    /**
     * Download processed image attachment securely forcing native browser save prompts.
     */
    public function download(Request $request)
    {
        $path = $request->query('path');
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Requested processed file not found or has expired.');
        }

        $record = ProcessedImage::where('disk_path', $path)->first();
        $cleanName = 'optimized_image.' . pathinfo($path, PATHINFO_EXTENSION);
        if ($record && $record->original_name) {
            $cleanName = 'optimized_' . pathinfo($record->original_name, PATHINFO_FILENAME) . '.' . $record->format;
        }

        return response()->download(Storage::disk('public')->path($path), $cleanName);
    }

    /**
     * Store generated QR code matrix parameters to cloud history logs.
     */
    public function storeQrCode(Request $request)
    {
        $request->validate([
            'profile_type' => ['required', 'string'],
            'summary_payload' => ['required', 'string'],
            'foreground_color' => ['nullable', 'string'],
            'background_color' => ['nullable', 'string'],
            'matrix_size' => ['nullable', 'integer'],
            'redundancy_level' => ['nullable', 'string'],
        ]);

        try {
            $user = Auth::guard('web')->user();
            $record = StoredQrCode::create([
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'profile_type' => $request->input('profile_type'),
                'summary_payload' => $request->input('summary_payload'),
                'foreground_color' => $request->input('foreground_color', '#8B5CF6'),
                'background_color' => $request->input('background_color', '#FFFFFF'),
                'matrix_size' => (int) $request->input('matrix_size', 280),
                'redundancy_level' => $request->input('redundancy_level', 'H'),
            ]);

            return response()->json([
                'success' => true,
                'record' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record QR matrix profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a processed image securely.
     */
    public function destroy(Request $request, ProcessedImage $processedImage)
    {
        $user = Auth::guard('web')->user();

        // Security check: ensure user owns the resource
        if ($user) {
            if ($processedImage->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized operation.'
                ], 403);
            }
        } else {
            // Guest check: must be null user_id and matching IP address
            if ($processedImage->user_id !== null || $processedImage->ip_address !== $request->ip()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized operation.'
                ], 403);
            }
        }

        try {
            // Delete file from disk if it exists
            if ($processedImage->disk_path && Storage::disk('public')->exists($processedImage->disk_path)) {
                Storage::disk('public')->delete($processedImage->disk_path);
            }

            // Delete DB record
            $processedImage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset: ' . $e->getMessage()
            ], 500);
        }
    }
}
