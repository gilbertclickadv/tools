<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class JwtDebuggerController extends Controller
{
    /**
     * Display the public JWT Debugger & Token Inspector tool.
     */
    public function index()
    {
        return Inertia::render('JwtDebugger');
    }
}
