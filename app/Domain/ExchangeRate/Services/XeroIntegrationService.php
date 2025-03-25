<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\Models\CurrencyExchange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class XeroIntegrationService
{
    protected $accessToken;
    protected $tenantId;
    protected $bankAccountId;
    protected $feeAccountId;
    protected $contactId;
    protected $currencyCode;

    public function __construct()
    {
        $this->accessToken = Cache::get('xero_access_token');
        $this->tenantId = config('services.xero.tenant_id');
        $this->bankAccountId = config('services.xero.bank_account_id');
        $this->feeAccountId = config('services.xero.fee_account_id');
        $this->contactId = config('services.xero.contact_id');
        $this->currencyCode = config('services.xero.currency_code', 'USD');
        
        if (empty($this->accessToken) || empty($this->tenantId)) {
            Log::warning('Xero integration is not properly configured');
        }
    }

    /**
     * Refresh the Xero access token using the refresh token
     *
     * @return bool Whether the token was successfully refreshed
     */
    public function refreshToken()
    {
        $refreshToken = Cache::get('xero_refresh_token');
        
        if (!$refreshToken) {
            Log::error('Cannot refresh Xero token: No refresh token available');
            return false;
        }
        
        try {
            $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.xero.client_id'),
                'client_secret' => config('services.xero.client_secret'),
            ]);
            
            if (!$response->successful()) {
                Log::error('Failed to refresh Xero token', [
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
            
            $data = $response->json();
            
            // Update the tokens in cache
            Cache::put('xero_access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
            Cache::put('xero_refresh_token', $data['refresh_token'], now()->addDays(30));
            
            // Update the instance variable
            $this->accessToken = $data['access_token'];
            
            Log::info('Successfully refreshed Xero token');
            return true;
        } catch (\Exception $e) {
            Log::error('Exception while refreshing Xero token', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a bill in Xero for a currency exchange
     *
     * @param CurrencyExchange $exchange
     * @return string|null The Xero Bill ID if successful
     */
    public function createBill(CurrencyExchange $exchange)
    {
        if (empty($this->accessToken) || empty($this->tenantId)) {
            Log::error('Cannot create Xero bill: Missing Xero credentials');
            $exchange->xero_status = 'failed';
            $exchange->save();
            return null;
        }

        try {
            // Prepare the bill data
            $bill = [
                'Type' => 'ACCPAY',
                'Contact' => [
                    'ContactID' => $this->contactId
                ],
                'Date' => $exchange->exchange_date->format('Y-m-d'),
                'DueDate' => $exchange->exchange_date->addDays(7)->format('Y-m-d'),
                'LineAmountTypes' => 'Exclusive',
                'Reference' => 'FX Exchange: USD to COP',
                'CurrencyCode' => $this->currencyCode,
                'Status' => 'AUTHORISED',
                'LineItems' => [
                    // Main currency exchange line item
                    [
                        'Description' => 'Currency Exchange USD to COP',
                        'Quantity' => 1,
                        'UnitAmount' => $exchange->usd_amount,
                        'AccountCode' => $this->bankAccountId,
                        'TaxType' => 'NONE',
                    ],
                    // Bank fee line item
                    [
                        'Description' => 'Bank Fee for Currency Exchange',
                        'Quantity' => 1,
                        'UnitAmount' => $exchange->bank_fee_usd,
                        'AccountCode' => $this->feeAccountId,
                        'TaxType' => 'NONE',
                    ]
                ],
            ];

            // Make the API call to Xero
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Xero-Tenant-Id' => $this->tenantId,
            ])->post('https://api.xero.com/api.xro/2.0/Invoices', ['Invoices' => [$bill]]);

            // If unauthorized, try refreshing the token and trying again
            if ($response->status() === 401) {
                Log::info('Xero token expired, attempting to refresh');
                if ($this->refreshToken()) {
                    // Retry with the new token
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Xero-Tenant-Id' => $this->tenantId,
                    ])->post('https://api.xero.com/api.xro/2.0/Invoices', ['Invoices' => [$bill]]);
                }
            }

            // Process the response
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['Invoices'][0]['InvoiceID'])) {
                    $billId = $responseData['Invoices'][0]['InvoiceID'];
                    
                    // Update the exchange record with Xero info
                    $exchange->xero_bill_id = $billId;
                    $exchange->xero_status = 'synced';
                    $exchange->xero_currency_rate = $responseData['Invoices'][0]['CurrencyRate'] ?? $exchange->exchange_rate;
                    $exchange->xero_reference = $responseData['Invoices'][0]['InvoiceNumber'] ?? null;
                    $exchange->save();
                    
                    Log::info('Xero bill created successfully', [
                        'exchange_id' => $exchange->id,
                        'xero_bill_id' => $billId
                    ]);
                    
                    return $billId;
                }
            }
            
            // If we get here, something went wrong
            Log::error('Failed to create Xero bill', [
                'exchange_id' => $exchange->id,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);
            
            $exchange->xero_status = 'failed';
            $exchange->save();
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exception while creating Xero bill', [
                'exchange_id' => $exchange->id,
                'error' => $e->getMessage()
            ]);
            
            $exchange->xero_status = 'failed';
            $exchange->save();
            
            return null;
        }
    }

    /**
     * Get exchange rates from Xero for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @param string $fromCurrency Base currency code
     * @param string $toCurrency Target currency code
     * @return float|null The exchange rate if available
     */
    public function getExchangeRate($date, $fromCurrency = 'USD', $toCurrency = 'COP')
    {
        if (empty($this->accessToken) || empty($this->tenantId)) {
            Log::error('Cannot get Xero exchange rate: Missing Xero credentials');
            return null;
        }

        try {
            // Call Xero currency API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Xero-Tenant-Id' => $this->tenantId,
            ])->get('https://api.xero.com/api.xro/2.0/Currencies');

            if ($response->successful()) {
                $currencies = $response->json()['Currencies'] ?? [];
                // Process the currencies and find the exchange rate
                // This is a simplified example - Xero doesn't directly provide exchange rates
                // You would need to implement your own logic based on your requirements
                
                return null; // Placeholder for actual implementation
            }
            
            Log::error('Failed to get currencies from Xero', [
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exception while getting Xero currencies', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
} 