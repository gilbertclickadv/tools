<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class JsonFormatterController extends Controller
{
    /**
     * Display the JSON Formatter dashboard.
     */
    public function index()
    {
        return Inertia::render('JsonFormatter');
    }
}
