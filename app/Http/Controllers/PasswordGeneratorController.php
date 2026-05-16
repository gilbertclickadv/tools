<?php

namespace App\Http\Controllers;

use App\Models\PasswordGeneration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PasswordGeneratorController extends Controller
{
    public function index()
    {
        return Inertia::render('PasswordGenerator');
    }

    /** Track generation settings for analytics (never store the password). */
    public function track(Request $request)
    {
        $validated = $request->validate([
            'length'        => ['required', 'integer', 'min:4', 'max:128'],
            'use_uppercase' => ['boolean'],
            'use_lowercase' => ['boolean'],
            'use_numbers'   => ['boolean'],
            'use_symbols'   => ['boolean'],
        ]);

        PasswordGeneration::create([
            'user_id'       => auth()->id(),
            'ip_address'    => $request->ip(),
            'length'        => $validated['length'],
            'use_uppercase' => $validated['use_uppercase'] ?? true,
            'use_lowercase' => $validated['use_lowercase'] ?? true,
            'use_numbers'   => $validated['use_numbers'] ?? true,
            'use_symbols'   => $validated['use_symbols'] ?? false,
            'generated_at'  => now(),
        ]);

        return response()->noContent();
    }
}
