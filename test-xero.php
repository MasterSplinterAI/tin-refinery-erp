<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

echo "Checking Xero connection...\n";

// Check for access token
$accessToken = Cache::get('xero_access_token');
echo "Access token: " . ($accessToken ? "EXISTS (" . substr($accessToken, 0, 10) . "...)" : "MISSING") . "\n";

// Check for refresh token
$refreshToken = Cache::get('xero_refresh_token');
echo "Refresh token: " . ($refreshToken ? "EXISTS (" . substr($refreshToken, 0, 10) . "...)" : "MISSING") . "\n";

// Check for tenant ID
$tenantId = Cache::get('xero_tenant_id');
echo "Tenant ID: " . ($tenantId ? $tenantId : "MISSING") . "\n";

// If we have an access token, try to get currencies
if ($accessToken) {
    try {
        echo "\nTrying to get currencies from Xero...\n";
        $xeroService = app('xero');
        $currencies = $xeroService->getCurrencies();
        echo "Success! Got currencies from Xero:\n";
        print_r($currencies);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        
        // Try to get the tenant ID if missing
        if (!$tenantId) {
            try {
                echo "\nTrying to get tenant ID from Xero...\n";
                $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                    ->get('https://api.xero.com/connections');
                
                if ($response->successful()) {
                    $connections = $response->json();
                    if (!empty($connections)) {
                        $newTenantId = $connections[0]['tenantId'] ?? null;
                        if ($newTenantId) {
                            echo "Found tenant ID: $newTenantId\n";
                            Cache::put('xero_tenant_id', $newTenantId, now()->addDays(30));
                            echo "Cached tenant ID for 30 days.\n";
                            
                            // Try again with the tenant ID
                            echo "\nTrying again with the new tenant ID...\n";
                            try {
                                $currencies = $xeroService->getCurrencies();
                                echo "Success! Got currencies from Xero:\n";
                                print_r($currencies);
                            } catch (\Exception $e2) {
                                echo "Still failed: " . $e2->getMessage() . "\n";
                            }
                        } else {
                            echo "No tenant ID found in response.\n";
                        }
                    } else {
                        echo "No connections found in response.\n";
                    }
                } else {
                    echo "Failed to get connections: " . $response->body() . "\n";
                }
            } catch (\Exception $e2) {
                echo "Error getting tenant ID: " . $e2->getMessage() . "\n";
            }
        }
    }
} else {
    echo "No access token, can't connect to Xero.\n";
} 