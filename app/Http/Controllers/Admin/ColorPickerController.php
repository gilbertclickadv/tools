<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ColorSwatch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ColorPickerController extends Controller
{
    /**
     * Display the Color Picker analytics dashboard in the admin portal.
     */
    public function index()
    {
        $stats = [
            'totalSwatches' => ColorSwatch::count(),
            'activeDesigners' => ColorSwatch::distinct('user_id')->count(),
            'todaySaves' => ColorSwatch::whereDate('created_at', today())->count(),
            'namedSwatches' => ColorSwatch::whereNotNull('name')->where('name', '!=', '')->count(),
        ];

        // 14-day creation chart
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date' => now()->subDays($i)->format('M d'),
                'count' => ColorSwatch::whereDate('created_at', $date)->count(),
            ];
        }

        // Top 5 Popular Colors
        $popularColors = ColorSwatch::selectRaw('hex, count(*) as count')
            ->groupBy('hex')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'hex' => $item->hex,
                'count' => $item->count,
            ]);

        // Recent Saved Swatches with User details
        $recentSwatches = ColorSwatch::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($swatch) => [
                'id' => $swatch->id,
                'hex' => $swatch->hex,
                'name' => $swatch->name,
                'user' => $swatch->user ? ['name' => $swatch->user->name, 'email' => $swatch->user->email] : null,
                'created_at' => $swatch->created_at->format('M d, H:i'),
            ]);

        return Inertia::render('Admin/ColorPicker', [
            'stats' => $stats,
            'chartData' => $chartData,
            'popularColors' => $popularColors,
            'recentSwatches' => $recentSwatches,
        ]);
    }
}
