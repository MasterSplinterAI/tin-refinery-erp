<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'session' => [
                    'id' => session()->getId(),
                    'has' => session()->has('auth'),
                    'all' => session()->all(),
                ],
            ],
            'csrf_token' => csrf_token(),
            'appUrl' => config('app.url'),
            'assetUrl' => config('app.asset_url') ?: config('app.url'),
            'viteUrl' => $this->getViteUrl(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
    
    /**
     * Get the Vite server URL based on the environment
     */
    protected function getViteUrl(): string
    {
        if (app()->environment('local')) {
            return 'http://localhost:5176';
        }
        
        return config('app.url');
    }
}
