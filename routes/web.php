<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ImageProcessingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Tools Hub (Home) ────────────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// ─── Image Studio ─────────────────────────────────────────────────────────────
Route::get('/tools/image', function () {
    $user  = auth()->user();
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
                'id'            => $img->id,
                'original_name' => $img->original_name,
                'format'        => strtoupper($img->format),
                'size_bytes'    => $img->size_bytes,
                'expires_at'    => $img->expires_at ? $img->expires_at->diffForHumans() : null,
                'download_url'  => route('image.download', ['path' => $img->disk_path]),
                'output_url'    => \Illuminate\Support\Facades\Storage::disk('public')->url($img->disk_path),
                'created_at'    => $img->created_at->diffForHumans(),
            ];
        });

    return Inertia::render('ImageStudio', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
        'initialHistory' => $history,
    ]);
})->name('tools.image');

// ─── QR Code Generator ────────────────────────────────────────────────────────
Route::get('/tools/qr-code', function () {
    return Inertia::render('QrGenerator', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('tools.qr');

// ─── Legacy redirect (301 SEO-safe) ──────────────────────────────────────────
Route::permanentRedirect('/qr-code-generator', '/tools/qr-code');

use App\Http\Controllers\UrlShortenerController;
// ─── URL Shortener ────────────────────────────────────────────────────────────
Route::get('/tools/url-shortener', [UrlShortenerController::class, 'index'])->name('tools.url-shortener');
Route::post('/api/shorten', [UrlShortenerController::class, 'store'])->name('url.shorten');
Route::delete('/api/shorten/{shortUrl}', [UrlShortenerController::class, 'destroy'])->name('url.destroy');

// ─── Short URL Redirect ───────────────────────────────────────────────────────
Route::get('/s/{code}', [UrlShortenerController::class, 'redirect'])->name('url.redirect');

// ─── UUID Generator ────────────────────────────────────────────────────────────
use App\Http\Controllers\UuidGeneratorController;
Route::get('/tools/uuid-generator', [UuidGeneratorController::class, 'index'])->name('tools.uuid-generator');
Route::post('/api/uuid/generate', [UuidGeneratorController::class, 'store'])->name('uuid.generate');
Route::delete('/api/uuid/bulk', [UuidGeneratorController::class, 'bulkDestroy'])->name('uuid.bulk-destroy');
Route::delete('/api/uuid/{generatedUuid}', [UuidGeneratorController::class, 'destroy'])->name('uuid.destroy');

// ─── Password Generator ─────────────────────────────────────────────────────
use App\Http\Controllers\PasswordGeneratorController;
Route::get('/tools/password-generator', [PasswordGeneratorController::class, 'index'])->name('tools.password-generator');
Route::post('/api/password/track', [PasswordGeneratorController::class, 'track'])->name('password.track');

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
    
    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    // Using put/patch for updates and delete for destruction
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/url-shortener', [\App\Http\Controllers\Admin\UrlShortenerController::class, 'index'])->name('url-shortener.index');
    Route::patch('/url-shortener/{shortUrl}/toggle', [\App\Http\Controllers\Admin\UrlShortenerController::class, 'toggle'])->name('url-shortener.toggle');
    Route::delete('/url-shortener/{shortUrl}', [\App\Http\Controllers\Admin\UrlShortenerController::class, 'destroy'])->name('url-shortener.destroy');

    Route::get('/uuid-generator', [\App\Http\Controllers\Admin\UuidGeneratorController::class, 'index'])->name('uuid-generator.index');
    Route::delete('/uuid-generator/{generatedUuid}', [\App\Http\Controllers\Admin\UuidGeneratorController::class, 'destroy'])->name('uuid-generator.destroy');
    Route::get('/password-generator', [\App\Http\Controllers\Admin\PasswordGeneratorController::class, 'index'])->name('password-generator.index');
});

require __DIR__.'/auth.php';
