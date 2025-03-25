<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xero Test Page</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #444;
            margin-top: 0;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 5px 0;
        }
        .btn-secondary {
            background-color: #2196F3;
        }
        .btn-warning {
            background-color: #ff9800;
        }
        .note {
            background-color: #f9f9f9;
            border-left: 4px solid #2196F3;
            padding: 10px 15px;
            margin: 15px 0;
        }
        code {
            background-color: #f0f0f0;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Xero Integration Test Page</h1>
    
    <div class="card">
        <h2>Test the Full Flow</h2>
        <p>Click the button below to start the complete Xero authorization test flow:</p>
        <a href="{{ url('/xero-test-start') }}" class="btn">Start Test Flow</a>
        
        <div class="note">
            <p>This will simulate the full OAuth flow, redirecting to the callback page with test parameters.</p>
        </div>
    </div>
    
    <div class="card">
        <h2>Direct Test of Callback</h2>
        <p>Test the callback directly with parameters:</p>
        <a href="{{ url('/xero-test-callback?code=test_code&state=test_state') }}" class="btn btn-secondary">Test Callback</a>
        
        <div class="note">
            <p>This directly accesses the callback with test parameters, skipping the initial redirect.</p>
        </div>
    </div>
    
    <div class="card">
        <h2>Real Xero Authorization</h2>
        <p>Connect to your actual Xero account:</p>
        <a href="{{ url('/xero-direct') }}" class="btn btn-warning">Connect to Xero</a>
        
        <div class="note">
            <p>This initiates a real OAuth flow with Xero. You will be redirected to Xero's login page.</p>
        </div>
    </div>
    
    <div class="card">
        <h2>Return to Dashboard</h2>
        <p>Go back to the main application:</p>
        <a href="{{ url('/dashboard') }}" class="btn" style="background-color: #666;">Dashboard</a>
    </div>
    
    <div class="card">
        <h2>Debug Information</h2>
        <p>Current session ID: <code>{{ session()->getId() }}</code></p>
        <p>App URL: <code>{{ config('app.url') }}</code></p>
        <p>Xero redirect URI: <code>{{ env('XERO_REDIRECT_URI') }}</code></p>
    </div>
</body>
</html> 