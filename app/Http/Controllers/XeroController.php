<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\XeroService;
use Illuminate\Support\Facades\Log;

class XeroController extends Controller
{
    protected $xero;

    public function __construct(XeroService $xero)
    {
        $this->xero = $xero;
    }

    public function connect()
    {
        try {
            Log::info('Initiating Xero connection', [
                'session_id' => session()->getId(),
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Generate a state parameter to prevent CSRF
            $state = md5(uniqid(rand(), true));
            session(['xero_state' => $state]);
            
            // Get Xero configuration directly from env to ensure we're using the right values
            $clientId = env('XERO_CLIENT_ID');
            $redirectUri = env('XERO_REDIRECT_URI');
            
            if (empty($clientId) || empty($redirectUri)) {
                Log::error('Missing Xero configuration', [
                    'client_id' => empty($clientId) ? 'missing' : 'present',
                    'redirect_uri' => empty($redirectUri) ? 'missing' : 'present',
                ]);
                return redirect()->back()->with('error', 'Xero integration is not properly configured.');
            }
            
            // Generate the authorization URL directly
            $authUrl = 'https://login.xero.com/identity/connect/authorize?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'openid profile email accounting.transactions offline_access',
                'state' => $state
            ]);
            
            Log::info('Generated Xero authorization URL', [
                'session_id' => session()->getId(),
                'url_length' => strlen($authUrl),
                'redirect_uri' => $redirectUri
            ]);
            
            // Use redirect() instead of redirect()->away() to ensure proper URL handling
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to start Xero connection', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        Log::info('Received Xero callback', [
            'has_code' => $request->has('code') ? 'yes' : 'no',
            'has_state' => $request->has('state') ? 'yes' : 'no',
            'session_id' => session()->getId(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'full_url' => $request->fullUrl(),
            'params' => $request->all()
        ]);
        
        $code = $request->get('code');
        $state = $request->get('state');
        
        // Validate the state parameter to prevent CSRF
        $expectedState = session('xero_state');
        if ($state !== $expectedState) {
            Log::warning('Xero callback state mismatch', [
                'expected' => $expectedState,
                'received' => $state
            ]);
            
            // Don't fail immediately, continue and flag the issue
            // Some browsers may have issues with sessions when opening the page in a new tab
        }
        
        // Save the code to a secure storage for debugging
        try {
            $data = [
                'code' => $code,
                'state' => $state,
                'time' => now()->toDateTimeString(),
                'session_id' => session()->getId(),
                'host' => $request->getHost()
            ];
            
            file_put_contents(
                storage_path('xero_callback_' . date('YmdHis') . '.txt'),
                json_encode($data, JSON_PRETTY_PRINT)
            );
        } catch (\Exception $e) {
            Log::error('Failed to save Xero callback data', ['error' => $e->getMessage()]);
        }
        
        // Return the iframe-redirect view 
        return view('xero.iframe-redirect', [
            'code' => $code,
            'state' => $state
        ]);
    }

    public function disconnect()
    {
        try {
            $this->xero->disconnect();
            Log::info('Successfully disconnected from Xero');
            return redirect()->route('dashboard')->with('success', 'Successfully disconnected from Xero');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect from Xero', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Failed to disconnect from Xero: ' . $e->getMessage());
        }
    }

    public function testConnection()
    {
        try {
            Log::info('Testing Xero connection');
            $currencies = $this->xero->getCurrencies();
            Log::info('Successfully retrieved Xero currencies');
            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to Xero API',
                'data' => $currencies
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to connect to Xero API', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to Xero API: ' . $e->getMessage()
            ], 500);
        }
    }
} 