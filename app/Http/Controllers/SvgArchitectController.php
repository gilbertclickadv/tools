<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class SvgArchitectController extends Controller
{
    /**
     * Display the public SVG Architect & Path Optimizer.
     */
    public function index()
    {
        return Inertia::render('SvgArchitect');
    }
}
