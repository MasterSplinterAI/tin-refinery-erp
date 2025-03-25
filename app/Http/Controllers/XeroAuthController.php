<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class XeroAuthController extends Controller
{
    public function redirect()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.xero.client_id'),
            'redirect_uri' => config('services.xero.redirect'),
            'scope' => config('services.xero.scope'),
            'state' => csrf_token()
        ]);

        return redirect('https://login.xero.com/identity/connect/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', 'Xero authorization failed: ' . $request->error);
        }

        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'No authorization code received from Xero');
        }

        try {
            $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
                'redirect_uri' => config('services.xero.redirect'),
                'client_id' => config('services.xero.client_id'),
                'client_secret' => config('services.xero.client_secret'),
            ]);

            if (!$response->successful()) {
                return redirect()->route('dashboard')->with('error', 'Failed to get access token from Xero');
            }

            $data = $response->json();
            
            // Store the tokens in cache
            Cache::put('xero_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            Cache::put('xero_refresh_token', $data['refresh_token'], now()->addDays(30));
            
            // Get connections to retrieve tenant ID
            $connectionsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $data['access_token'],
                'Content-Type' => 'application/json'
            ])->get('https://api.xero.com/connections');
            
            if ($connectionsResponse->successful()) {
                $connections = $connectionsResponse->json();
                if (!empty($connections)) {
                    // Store the tenant ID in the config
                    $tenantId = $connections[0]['tenantId'];
                    Config::set('services.xero.tenant_id', $tenantId);
                    
                    // Also store in .env file for persistence
                    $this->updateEnvVariable('XERO_TENANT_ID', $tenantId);
                }
            }

            return redirect()->route('dashboard')->with('success', 'Successfully connected to Xero!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Error connecting to Xero: ' . $e->getMessage());
        }
    }

    /**
     * Update an environment variable in the .env file
     * 
     * @param string $key The key to update
     * @param string $value The new value
     * @return bool Whether the update was successful
     */
    private function updateEnvVariable($key, $value)
    {
        try {
            $path = app()->environmentFilePath();
            $content = file_get_contents($path);
            
            // If the key exists, replace its value
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                // Otherwise, add the key-value pair
                $content .= "\n{$key}={$value}";
            }
            
            file_put_contents($path, $content);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update .env file: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect()
    {
        Cache::forget('xero_access_token');
        Cache::forget('xero_refresh_token');
        
        return redirect()->route('dashboard')->with('success', 'Successfully disconnected from Xero');
    }
} 