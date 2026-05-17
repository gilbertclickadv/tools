<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class Base64Controller extends Controller
{
    /**
     * Display the public Base64 Encoder/Decoder dashboard.
     */
    public function index()
    {
        return Inertia::render('Base64');
    }
}
