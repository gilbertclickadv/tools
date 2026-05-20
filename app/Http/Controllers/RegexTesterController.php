<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class RegexTesterController extends Controller
{
    /**
     * Display the public Regex Tester & Visual Explainer.
     */
    public function index()
    {
        return Inertia::render('RegexTester');
    }
}
