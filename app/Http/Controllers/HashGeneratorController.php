<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class HashGeneratorController extends Controller
{
    /**
     * Display the public Hash Generator dashboard.
     */
    public function index()
    {
        return Inertia::render('HashGenerator');
    }
}
