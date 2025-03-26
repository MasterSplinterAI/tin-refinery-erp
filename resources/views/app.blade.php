<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-url" content="{{ config('app.url') }}">
        <!-- Force HTTPS for all requests -->
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Routes must be included before the app -->
        @routes
        
        <!-- Load compiled assets directly when using ngrok -->
        @if(str_contains(request()->getHost(), 'ngrok'))
            <link rel="stylesheet" href="{{ asset('build/assets/app-BkP60gpZ.css') }}">
            <script type="module" src="{{ asset('build/assets/app-52CAVntt.js') }}"></script>
        @else
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <!-- Simple loader that shows while app initializes -->
        <div id="app-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f3f4f6; display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; border: 4px solid #ddd; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                <p>Loading application...</p>
            </div>
        </div>
        
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
        
        <script>
            // Hide loader once app is ready
            window.addEventListener('load', function() {
                // Give a short delay to ensure app is truly ready
                setTimeout(function() {
                    const loader = document.getElementById('app-loading');
                    if (loader) {
                        loader.style.display = 'none';
                    }
                }, 500);
            });
            
            // Make a window.onerror handler to track any loading issues
            window.onerror = function(message, source, lineno, colno, error) {
                console.error('Global error:', { message, source, lineno, colno, error });
                return false;
            };
        </script>
        
        @inertia
    </body>
</html>
