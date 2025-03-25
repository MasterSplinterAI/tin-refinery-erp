<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">
    <!-- Force HTTPS for all requests -->
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    
    <title>{{ config('app.name', 'Tin Refinery') }}</title>
    
    <!-- Routes must be included before the app -->
    @routes
    
    <!-- Load compiled assets directly when using ngrok -->
    @if(str_contains(request()->getHost(), 'ngrok'))
        <link rel="stylesheet" href="{{ asset('build/assets/app-o2VBFKFS.css') }}">
        <script type="module" src="{{ asset('build/assets/app-y-fcL2YG.js') }}"></script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html> 