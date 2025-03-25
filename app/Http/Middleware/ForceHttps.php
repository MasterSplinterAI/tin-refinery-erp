<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->getHost(), 'ngrok-free.app')) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
} 