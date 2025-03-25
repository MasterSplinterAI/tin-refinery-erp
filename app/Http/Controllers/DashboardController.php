<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Check if we have a Xero access token in the cache
        $xero_connected = Cache::has('xero_access_token');

        return Inertia::render('Dashboard', [
            'xero_connected' => $xero_connected,
            'auth' => [
                'user' => Auth::user(),
            ],
            'appUrl' => config('app.url'),
        ]);
    }
} 