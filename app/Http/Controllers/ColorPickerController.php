<?php

namespace App\Http\Controllers;

use App\Models\ColorSwatch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ColorPickerController extends Controller
{
    /**
     * Display the public Color Picker dashboard.
     */
    public function index(Request $request)
    {
        $dbSwatches = [];
        if (auth()->check()) {
            $dbSwatches = auth()->user()->colorSwatches()
                ->latest()
                ->get()
                ->map(fn($swatch) => [
                    'hex' => $swatch->hex,
                    'name' => $swatch->name,
                ]);
        }

        return Inertia::render('ColorPicker', [
            'dbSwatches' => $dbSwatches,
        ]);
    }

    /**
     * Store a color swatch in the database.
     */
    public function storeSwatch(Request $request)
    {
        $validated = $request->validate([
            'hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'name' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth()->user();

        // Enforce the 24 swatch limit
        $swatchesCount = $user->colorSwatches()->count();
        if ($swatchesCount >= 24) {
            $user->colorSwatches()->oldest()->first()?->delete();
        }

        // Save or update swatch
        $swatch = $user->colorSwatches()->updateOrCreate(
            ['hex' => $validated['hex']],
            ['name' => $validated['name']]
        );

        return response()->json([
            'success' => true,
            'swatch' => [
                'hex' => $swatch->hex,
                'name' => $swatch->name,
            ],
        ]);
    }

    /**
     * Update/rename a color swatch in the database.
     */
    public function updateSwatch(Request $request)
    {
        $validated = $request->validate([
            'hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'name' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth()->user();
        $swatch = $user->colorSwatches()->where('hex', $validated['hex'])->first();

        if ($swatch) {
            $swatch->update(['name' => $validated['name']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a color swatch from the database.
     */
    public function deleteSwatch(Request $request)
    {
        $validated = $request->validate([
            'hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $user = auth()->user();
        $user->colorSwatches()->where('hex', $validated['hex'])->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Clear all color swatches from the database.
     */
    public function clearSwatches()
    {
        $user = auth()->user();
        $user->colorSwatches()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Sync local storage guest swatches with the database on login.
     */
    public function syncSwatches(Request $request)
    {
        $validated = $request->validate([
            'swatches' => ['required', 'array', 'max:24'],
            'swatches.*.hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'swatches.*.name' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth()->user();

        // Process in reverse so the order of unshifted local colors is preserved
        $reversed = array_reverse($validated['swatches']);

        foreach ($reversed as $swatchData) {
            $swatchesCount = $user->colorSwatches()->count();
            if ($swatchesCount >= 24) {
                $user->colorSwatches()->oldest()->first()?->delete();
            }

            $user->colorSwatches()->updateOrCreate(
                ['hex' => $swatchData['hex']],
                ['name' => $swatchData['name']]
            );
        }

        $dbSwatches = $user->colorSwatches()
            ->latest()
            ->get()
            ->map(fn($swatch) => [
                'hex' => $swatch->hex,
                'name' => $swatch->name,
            ]);

        return response()->json([
            'success' => true,
            'swatches' => $dbSwatches,
        ]);
    }
}
