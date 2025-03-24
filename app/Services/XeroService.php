<?php

namespace App\Services;

use App\Providers\XeroServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class XeroService
{
    protected $xeroProvider;
    protected $apiUrl = 'https://api.xero.com/api.xro/2.0';

    public function __construct(XeroServiceProvider $xeroProvider)
    {
        $this->xeroProvider = $xeroProvider;
    }

    public function getAuthorizationUrl()
    {
        Log::info('Generating Xero authorization URL');
        return $this->xeroProvider->getAuthorizationUrl();
    }

    public function getAccessToken($code)
    {
        try {
            Log::info('Getting access token from code', ['code' => substr($code, 0, 5) . '...' . substr($code, -5)]);
            return $this->xeroProvider->getAccessToken($code);
        } catch (\Exception $e) {
            // Check if this is an "invalid_grant" error 
            if (strpos($e->getMessage(), 'invalid_grant') !== false) {
                Log::warning('Invalid grant error when getting access token. This may be because the code was already used or expired.');
                
                // Disconnect to clear any existing tokens
                $this->disconnect();
                
                // Re-throw with a more helpful message
                throw new \Exception(
                    'The authorization code has expired or already been used. Please try connecting to Xero again.',
                    0,
                    $e
                );
            }
            
            // Re-throw the original exception for other errors
            throw $e;
        }
    }

    public function disconnect()
    {
        Log::info('Disconnecting from Xero');
        // Clear the tokens from cache
        Cache::forget('xero_access_token');
        Cache::forget('xero_refresh_token');
        return true;
    }

    public function refreshToken()
    {
        Log::info('Explicitly refreshing Xero token');
        return $this->xeroProvider->refreshToken();
    }

    public function getOrganisations()
    {
        try {
            $token = $this->xeroProvider->getAccessTokenFromCache();
            Log::info('Getting Xero organisations');
            
            $response = Http::withToken($token)
                ->get($this->apiUrl . '/Organisation');

            Log::info('Xero organisation response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'body_snippet' => substr($response->body(), 0, 100) . '...'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to get organisations: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Failed to get organisations', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getCurrencies()
    {
        try {
            return $this->xeroProvider->getExchangeRates();
        } catch (\Exception $e) {
            Log::error('Failed to get currencies', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a bank transaction in Xero for a currency exchange
     *
     * @param array $data The currency exchange data
     * @return array The response from Xero
     */
    public function createCurrencyExchange($data)
    {
        try {
            $token = $this->xeroProvider->getAccessTokenFromCache();
            $tenantId = Cache::get('xero_tenant_id');
            
            if (!$tenantId) {
                // Get tenant ID if not in cache
                $connections = Http::withToken($token)
                    ->get('https://api.xero.com/connections')
                    ->json();
                
                $tenantId = $connections[0]['tenantId'] ?? null;
                if (!$tenantId) {
                    throw new \Exception('Could not determine tenant ID from connections');
                }
                
                Cache::put('xero_tenant_id', $tenantId, now()->addDays(30));
            }
            
            // Prepare the bank transaction for the currency exchange
            // This is a simplified version - you'll need to adapt this to your exact GL accounts
            $bankTransaction = [
                'Type' => 'RECEIVE', // or 'SPEND' depending on your workflow
                'Contact' => [
                    'Name' => $data['bank_name'] ?? 'Currency Exchange'
                ],
                'LineItems' => [
                    [
                        'Description' => 'Currency Exchange: USD to COP',
                        'Quantity' => 1,
                        'UnitAmount' => $data['usd_amount'],
                        'AccountCode' => $data['from_account_code'], // USD Bank Account
                        'TaxType' => 'NONE'
                    ],
                    [
                        'Description' => 'Currency Exchange: Received COP',
                        'Quantity' => 1,
                        'UnitAmount' => $data['cop_amount'],
                        'AccountCode' => $data['to_account_code'], // COP Bank Account
                        'TaxType' => 'NONE'
                    ]
                ],
                'BankAccount' => [
                    'Code' => $data['bank_account_code'] // The main bank account being used
                ],
                'Date' => $data['date'] ?? now()->format('Y-m-d'),
                'CurrencyCode' => 'USD', // Base currency
                'Reference' => $data['reference'] ?? ('FX-' . now()->format('Ymd')),
                'Status' => 'AUTHORISED'
            ];
            
            // If there's a bank fee, add it as another line item
            if (!empty($data['bank_fee']) && $data['bank_fee'] > 0) {
                $bankTransaction['LineItems'][] = [
                    'Description' => 'Bank Fee for Currency Exchange',
                    'Quantity' => 1,
                    'UnitAmount' => $data['bank_fee'],
                    'AccountCode' => $data['fee_account_code'], // Bank Fee account
                    'TaxType' => 'NONE'
                ];
            }
            
            Log::info('Creating currency exchange in Xero', [
                'bank_transaction' => $bankTransaction
            ]);
            
            // Post to Xero API
            $response = Http::withToken($token)
                ->withHeaders([
                    'Xero-Tenant-Id' => $tenantId,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/BankTransactions', [
                    'BankTransactions' => [$bankTransaction]
                ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to create bank transaction: ' . $response->body());
            }
            
            $result = $response->json();
            Log::info('Successfully created currency exchange in Xero', [
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to create currency exchange in Xero', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get all accounts from Xero
     * 
     * @return array The accounts from Xero
     */
    public function getAccounts()
    {
        try {
            $token = $this->xeroProvider->getAccessTokenFromCache();
            $tenantId = Cache::get('xero_tenant_id');
            
            if (!$tenantId) {
                throw new \Exception('No tenant ID available. Please reconnect to Xero.');
            }
            
            $response = Http::withToken($token)
                ->withHeaders([
                    'Xero-Tenant-Id' => $tenantId,
                    'Accept' => 'application/json'
                ])
                ->get($this->apiUrl . '/Accounts');
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get accounts: ' . $response->body());
            }
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to get accounts from Xero', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get bank accounts from Xero (filtered accounts)
     * 
     * @return array Bank accounts
     */
    public function getBankAccounts()
    {
        try {
            $accounts = $this->getAccounts();
            
            // Filter to only get bank accounts
            return array_filter($accounts['Accounts'] ?? [], function($account) {
                return $account['Type'] === 'BANK';
            });
        } catch (\Exception $e) {
            Log::error('Failed to get bank accounts from Xero', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 