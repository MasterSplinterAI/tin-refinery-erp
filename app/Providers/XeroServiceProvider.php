<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\XeroService;

class XeroServiceProvider extends ServiceProvider
{
    protected $baseUrl = 'https://identity.xero.com';
    protected $apiUrl = 'https://api.xero.com/api.xro/2.0';
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $scope;

    public function __construct($app)
    {
        parent::__construct($app);
    }

    public function boot()
    {
        $this->clientId = config('services.xero.client_id');
        $this->clientSecret = config('services.xero.client_secret');
        $this->redirectUri = config('services.xero.redirect');
        $this->scope = config('services.xero.scope');

        Log::info('XeroServiceProvider initialized', [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope
        ]);
    }

    public function register()
    {
        $this->app->singleton(XeroService::class, function ($app) {
            return new XeroService($this);
        });

        $this->app->alias(XeroService::class, 'xero');
    }

    public function getAuthorizationUrl()
    {
        // Get the state from session
        $state = session('xero_state', md5(uniqid(rand(), true)));
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope,
            'state' => $state,
        ];

        $url = $this->baseUrl . '/connect/authorize?' . http_build_query($params);
        Log::info('Generated Xero authorization URL', [
            'url' => $url,
            'redirect_uri' => $this->redirectUri,
            'state' => $state
        ]);
        return $url;
    }

    public function getAccessToken($code)
    {
        Log::info('Attempting to get Xero access token', [
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ]);
        
        $response = Http::asForm()->post($this->baseUrl . '/connect/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        Log::info('Xero token response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers()
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Cache::put('xero_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            Cache::put('xero_refresh_token', $data['refresh_token'], now()->addDays(30));
            Log::info('Successfully stored Xero tokens', [
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type'] ?? 'unknown'
            ]);
            return $data;
        }

        throw new \Exception('Failed to get access token: ' . $response->body());
    }

    public function refreshToken()
    {
        $refreshToken = Cache::get('xero_refresh_token');
        Log::info('Attempting to refresh Xero token', [
            'has_refresh_token' => !empty($refreshToken),
            'client_id' => $this->clientId
        ]);

        if (!$refreshToken) {
            throw new \Exception('No refresh token available. Please reconnect to Xero.');
        }

        $response = Http::asForm()->post($this->baseUrl . '/connect/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        Log::info('Xero refresh token response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers()
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Cache::put('xero_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            Cache::put('xero_refresh_token', $data['refresh_token'], now()->addDays(30));
            Log::info('Successfully refreshed Xero tokens', [
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type'] ?? 'unknown'
            ]);
            return $data;
        }

        throw new \Exception('Failed to refresh token: ' . $response->body());
    }

    public function getAccessTokenFromCache()
    {
        $token = Cache::get('xero_access_token');
        Log::info('Getting Xero access token from cache', [
            'has_token' => !empty($token),
            'cache_key' => 'xero_access_token'
        ]);

        if (!$token) {
            Log::info('No access token in cache, attempting refresh');
            return $this->refreshToken()['access_token'];
        }

        return $token;
    }

    public function getExchangeRates()
    {
        try {
            $token = $this->getAccessTokenFromCache();
            Log::info('Attempting to get Xero exchange rates', [
                'has_token' => !empty($token)
            ]);
            
            // First, try to get a tenant ID if we don't have one
            $tenantId = Cache::get('xero_tenant_id');
            if (!$tenantId) {
                Log::info('No tenant ID found, attempting to get connections');
                $connectionsResponse = Http::withToken($token)
                    ->get('https://api.xero.com/connections');
                
                if (!$connectionsResponse->successful()) {
                    throw new \Exception('Failed to get connections: ' . $connectionsResponse->body());
                }
                
                $connections = $connectionsResponse->json();
                if (empty($connections)) {
                    throw new \Exception('No Xero connections found for this token');
                }
                
                $tenantId = $connections[0]['tenantId'] ?? null;
                if (!$tenantId) {
                    throw new \Exception('Could not determine tenant ID from connections');
                }
                
                // Cache the tenant ID for future use
                Cache::put('xero_tenant_id', $tenantId, now()->addDays(30));
                Log::info('Cached tenant ID', ['tenant_id' => $tenantId]);
            }
            
            // Make the request for currencies with the tenant ID
            $response = Http::withToken($token)
                ->withHeaders([
                    'Xero-Tenant-Id' => $tenantId,
                    'Accept' => 'application/json'
                ])
                ->get($this->apiUrl . '/Currencies');

            Log::info('Xero currencies response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'body_snippet' => substr($response->body(), 0, 200),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to get currencies: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error in getExchangeRates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 