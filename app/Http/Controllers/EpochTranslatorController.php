<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class EpochTranslatorController extends Controller
{
    /**
     * Display the public Epoch & UNIX Timestamp Translator.
     */
    public function index()
    {
        return Inertia::render('EpochTranslator');
    }
}
