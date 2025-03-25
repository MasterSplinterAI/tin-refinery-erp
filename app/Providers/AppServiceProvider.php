<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure ngrok URL handling (must come first)
        $this->configureNgrokUrl();
        
        // Configure Vite for ngrok (if needed)
        $this->configureViteForNgrok();
        
        // Force prefetch for Vite assets
        Vite::prefetch(concurrency: 3);
        
        // Force HTTPS on production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        // For ngrok URLs, force HTTPS and update the URL handler
        if (str_contains(request()->getHost(), 'ngrok')) {
            // Force HTTPS for ngrok
            URL::forceScheme('https');
            
            // Force the correct root URL with the ngrok domain
            URL::forceRootUrl('https://' . request()->getHost());
        }
        
        // Always share the app URL with Inertia
        Inertia::share([
            'appUrl' => URL::to('/'),
        ]);

        // Register Vite manifest reader for direct asset access
        if (file_exists(public_path('build/.vite/manifest.json'))) {
            $manifest = json_decode(file_get_contents(public_path('build/.vite/manifest.json')), true);
            
            // Find CSS and JS entries
            $cssFile = null;
            $jsFile = null;
            
            if (isset($manifest['resources/css/app.css']['file'])) {
                $cssFile = $manifest['resources/css/app.css']['file'];
            }
            
            if (isset($manifest['resources/js/app.js']['file'])) {
                $jsFile = $manifest['resources/js/app.js']['file'];
            }
            
            // Make these available to all views
            view()->share('viteManifestCss', $cssFile);
            view()->share('viteManifestJs', $jsFile);
        }
    }
    
    /**
     * Configure the application to properly handle ngrok URLs
     */
    protected function configureNgrokUrl()
    {
        // Get the current request
        $request = request();
        
        // Check if we're accessing through ngrok
        if ($request && str_contains($request->getHost(), 'ngrok')) {
            // Ensure URLs are generated with the ngrok domain
            $scheme = 'https';
            $host = $request->getHost();
            
            // Update the URL in the config
            config(['app.url' => $scheme . '://' . $host]);
            
            // Update asset URL to point to the correct location
            config(['app.asset_url' => $scheme . '://' . $host]);
        }
    }
    
    /**
     * Configure Vite to work with ngrok
     */
    protected function configureViteForNgrok()
    {
        if (str_contains(request()->getHost(), 'ngrok')) {
            // Make sure Vite uses absolute URLs
            config(['vite.use_absolute_urls' => true]);
            
            // Set the ngrok URL as the base
            $ngrokUrl = 'https://' . request()->getHost();
            
            // Configure Vite to use the correct asset paths
            config(['vite.asset_url' => $ngrokUrl]);
            config(['vite.build_path' => 'build']);
            
            // Tell Vite where to find the manifest
            $manifestPath = public_path('build/.vite/manifest.json');
            if (file_exists($manifestPath)) {
                Vite::useManifestFilename('.vite/manifest.json');
            }
        }
    }
}
