<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class TextToolsController extends Controller
{
    /**
     * Display the public Text Tools suite.
     */
    public function index()
    {
        return Inertia::render('TextTools');
    }
}
