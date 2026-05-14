<?php

use App\Http\Controllers\ProfileController;
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
});

Route::get('/qr-code-generator', function () {
    return Inertia::render('QrGenerator', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\ImageProcessingController;

Route::post('/api/process-image', [ImageProcessingController::class, 'process'])->name('image.process');
Route::get('/api/download-image', [ImageProcessingController::class, 'download'])->name('image.download');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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
