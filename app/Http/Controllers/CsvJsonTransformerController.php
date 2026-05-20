<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class CsvJsonTransformerController extends Controller
{
    /**
     * Display the public CSV ⇆ JSON Data Transformer.
     */
    public function index()
    {
        return Inertia::render('CsvJsonTransformer');
    }
}
