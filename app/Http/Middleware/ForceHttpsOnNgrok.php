<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsOnNgrok
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (str_contains($request->getHost(), 'ngrok')) {
            Log::info('Ngrok request detected', [
                'host' => $request->getHost(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'secure' => $request->secure(),
                'headers' => $request->headers->all(),
            ]);
            
            // Force HTTPS
            URL::forceScheme('https');
            
            // Set secure cookies
            config(['session.secure' => true]);
            
            // Set trusted proxy headers
            $request->setTrustedProxies(
                [$request->getClientIp()],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
            );
        }
        
        return $next($request);
    }
}
