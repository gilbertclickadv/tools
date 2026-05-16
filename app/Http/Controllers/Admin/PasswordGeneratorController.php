<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordGeneration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PasswordGeneratorController extends Controller
{
    public function index()
    {
        $stats = [
            'total'      => PasswordGeneration::count(),
            'today'      => PasswordGeneration::whereDate('generated_at', today())->count(),
            'avgLength'  => (int) round(PasswordGeneration::avg('length') ?? 0),
            'withSymbols'=> PasswordGeneration::where('use_symbols', true)->count(),
        ];

        // 14-day chart
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date'  => now()->subDays($i)->format('M d'),
                'count' => PasswordGeneration::whereDate('generated_at', $date)->count(),
            ];
        }

        // Length distribution buckets
        $lengthBuckets = [
            '4–8'    => PasswordGeneration::whereBetween('length', [4, 8])->count(),
            '9–12'   => PasswordGeneration::whereBetween('length', [9, 12])->count(),
            '13–16'  => PasswordGeneration::whereBetween('length', [13, 16])->count(),
            '17–32'  => PasswordGeneration::whereBetween('length', [17, 32])->count(),
            '33–64'  => PasswordGeneration::whereBetween('length', [33, 64])->count(),
            '65–128' => PasswordGeneration::whereBetween('length', [65, 128])->count(),
        ];

        // Options usage %
        $total = max(1, $stats['total']);
        $optionStats = [
            ['label' => 'Uppercase', 'pct' => round(PasswordGeneration::where('use_uppercase', true)->count() / $total * 100)],
            ['label' => 'Lowercase', 'pct' => round(PasswordGeneration::where('use_lowercase', true)->count() / $total * 100)],
            ['label' => 'Numbers',   'pct' => round(PasswordGeneration::where('use_numbers', true)->count() / $total * 100)],
            ['label' => 'Symbols',   'pct' => round(PasswordGeneration::where('use_symbols', true)->count() / $total * 100)],
        ];

        return Inertia::render('Admin/PasswordGenerator', [
            'stats'        => $stats,
            'chartData'    => $chartData,
            'lengthBuckets'=> $lengthBuckets,
            'optionStats'  => $optionStats,
        ]);
    }
}
