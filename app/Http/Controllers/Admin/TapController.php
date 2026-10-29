<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function callback(Request $request)
    {
        return $request->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function webhook(Request $request)
    {
        Log::info($request->all());
    }
}
