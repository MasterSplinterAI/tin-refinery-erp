<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'xero/callback', // Exempt Xero callback from CSRF
        'login', // Temporarily exempt login to debug
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        // Add debugging for CSRF token issues
        if ($request->is('login') && $request->isMethod('post')) {
            Log::info('Login request details', [
                'token_present' => $request->hasHeader('X-CSRF-TOKEN'),
                'token_in_header' => $request->header('X-CSRF-TOKEN'),
                'token_in_input' => $request->input('_token'),
                'session_token' => $request->session()->token(),
                'has_session' => $request->hasSession(),
                'session_id' => $request->session()->getId(),
                'host' => $request->getHost(),
                'referer' => $request->header('referer'),
            ]);
        }
        
        return parent::handle($request, $next);
    }
} 