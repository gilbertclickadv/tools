<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\ImageProcessingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();
    $query = \App\Models\ProcessedImage::query();
    
    if ($user) {
        $query->where('user_id', $user->id);
    } else {
        $query->whereNull('user_id')->where('ip_address', request()->ip());
    }

    $history = $query->orderBy('created_at', 'desc')
        ->take(12)
        ->get()
        ->map(function ($img) {
            return [
                'id' => $img->id,
                'original_name' => $img->original_name,
                'format' => strtoupper($img->format),
                'size_bytes' => $img->size_bytes,
                'expires_at' => $img->expires_at ? $img->expires_at->diffForHumans() : null,
                'download_url' => route('image.download', ['path' => $img->disk_path]),
                'output_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($img->disk_path),
                'created_at' => $img->created_at->diffForHumans(),
            ];
        });

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'initialHistory' => $history,
    ]);
})->name('home');

Route::get('/qr-code-generator', function () {
    return Inertia::render('QrGenerator', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('qr.generator');

Route::post('/api/process-image', [ImageProcessingController::class, 'process'])->name('image.process');
Route::get('/api/download-image', [ImageProcessingController::class, 'download'])->name('image.download');
Route::post('/api/store-qr-code', [ImageProcessingController::class, 'storeQrCode'])->name('qr.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/history', function () {
        $user = auth()->user();
        $imageQuery = \App\Models\ProcessedImage::query();
        $qrQuery = \App\Models\StoredQrCode::query();

        if ($user) {
            $imageQuery->where('user_id', $user->id);
            $qrQuery->where('user_id', $user->id);
        } else {
            $imageQuery->whereNull('user_id')->where('ip_address', request()->ip());
            $qrQuery->whereNull('user_id')->where('ip_address', request()->ip());
        }

        $images = $imageQuery->orderBy('created_at', 'desc')->get()->map(function ($img) {
            return [
                'type' => 'image',
                'id' => $img->id,
                'original_name' => $img->original_name,
                'format' => strtoupper($img->format),
                'size_bytes' => $img->size_bytes,
                'expires_at' => $img->expires_at ? $img->expires_at->diffForHumans() : null,
                'download_url' => route('image.download', ['path' => $img->disk_path]),
                'created_at' => $img->created_at->diffForHumans(),
                'timestamp' => $img->created_at->timestamp,
            ];
        });

        $qrs = $qrQuery->orderBy('created_at', 'desc')->get()->map(function ($qr) {
            return [
                'type' => 'qr',
                'id' => $qr->id,
                'profile_type' => $qr->profile_type,
                'summary_payload' => $qr->summary_payload,
                'foreground_color' => $qr->foreground_color,
                'background_color' => $qr->background_color,
                'matrix_size' => $qr->matrix_size,
                'redundancy_level' => $qr->redundancy_level,
                'created_at' => $qr->created_at->diffForHumans(),
                'timestamp' => $qr->created_at->timestamp,
            ];
        });

        $combined = $images->concat($qrs)->sortByDesc('timestamp')->values();

        return Inertia::render('History', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'historyFeed' => $combined,
        ]);
    })->name('history');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Route Group
Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
