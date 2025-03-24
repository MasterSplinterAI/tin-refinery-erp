<?php

namespace App\Http\Controllers;

use App\Domain\ExchangeRate\Models\CurrencyExchange;
use App\Domain\ExchangeRate\Services\CurrencyExchangeService;
use App\Domain\ExchangeRate\Services\XeroIntegrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CurrencyExchangeController extends Controller
{
    protected $currencyExchangeService;
    protected $xeroIntegrationService;

    public function __construct(
        CurrencyExchangeService $currencyExchangeService,
        XeroIntegrationService $xeroIntegrationService = null
    ) {
        $this->currencyExchangeService = $currencyExchangeService;
        $this->xeroIntegrationService = $xeroIntegrationService;
    }

    public function index(Request $request)
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $exchanges = $this->currencyExchangeService->getExchanges($filters);
        $summary = $this->currencyExchangeService->getSummary($filters);

        return Inertia::render('CurrencyExchanges/Index', [
            'exchanges' => $exchanges,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exchange_date' => 'required|date',
            'usd_amount' => 'required|numeric|min:0',
            'cop_amount' => 'required|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0',
            'bank_fee_usd' => 'nullable|numeric|min:0',
            'bank_fee_cop' => 'nullable|numeric|min:0',
            'bank_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $exchange = $this->currencyExchangeService->createExchange($validated);

        return redirect()->back()->with('success', 'Currency exchange recorded successfully.');
    }

    /**
     * Sync a currency exchange with Xero
     * 
     * @param int $id The currency exchange ID
     * @return \Illuminate\Http\Response
     */
    public function syncWithXero($id)
    {
        try {
            // Get the currency exchange
            $exchange = CurrencyExchange::findOrFail($id);
            
            // Check if it's already synced
            if ($exchange->xero_synced) {
                return redirect()->back()->with('error', 'This exchange has already been synced with Xero.');
            }
            
            // Get the Xero service
            $xeroService = app()->make('xero');
            
            // Prepare the data for Xero
            $xeroData = [
                'usd_amount' => $exchange->usd_amount,
                'cop_amount' => $exchange->cop_amount,
                'bank_fee' => $exchange->bank_fee,
                'date' => $exchange->date->format('Y-m-d'),
                'reference' => 'FX-' . $exchange->id,
                'bank_name' => $exchange->bank_name,
                // These account codes should come from config or settings
                'from_account_code' => config('xero.accounts.usd_bank'),
                'to_account_code' => config('xero.accounts.cop_bank'),
                'fee_account_code' => config('xero.accounts.bank_fees'),
                'bank_account_code' => config('xero.accounts.main_bank')
            ];
            
            // Create the exchange in Xero
            $result = $xeroService->createCurrencyExchange($xeroData);
            
            // Update the exchange with Xero details
            $exchange->xero_synced = true;
            $exchange->xero_reference = $result['BankTransactions'][0]['BankTransactionID'] ?? null;
            $exchange->xero_sync_date = now();
            $exchange->save();
            
            return redirect()->back()->with('success', 'Currency exchange successfully synced with Xero.');
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Failed to sync currency exchange with Xero', [
                'exchange_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to sync with Xero: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Xero bank accounts for the exchange form
     *
     * @return \Illuminate\Http\Response
     */
    public function getXeroBankAccounts()
    {
        try {
            // Get the Xero service
            $xeroService = app()->make('xero');
            
            // Get the bank accounts
            $bankAccounts = $xeroService->getBankAccounts();
            
            return response()->json([
                'success' => true,
                'data' => $bankAccounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get bank accounts: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Retry failed Xero syncs
     *
     * @return \Illuminate\Http\Response
     */
    public function retryFailedSyncs()
    {
        try {
            // Get all unsynced exchanges
            $exchanges = CurrencyExchange::where('xero_synced', false)->get();
            
            if ($exchanges->isEmpty()) {
                return redirect()->back()->with('info', 'No failed syncs to retry.');
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($exchanges as $exchange) {
                try {
                    // Attempt to sync each exchange
                    $this->syncWithXero($exchange->id);
                    $successCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to retry sync for exchange', [
                        'exchange_id' => $exchange->id,
                        'error' => $e->getMessage()
                    ]);
                    $failCount++;
                }
            }
            
            return redirect()->back()->with('success', "Sync retry completed. Success: {$successCount}, Failed: {$failCount}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to retry syncs: ' . $e->getMessage());
        }
    }

    /**
     * Check the Xero connection status
     * 
     * @return \Illuminate\Http\Response
     */
    public function checkXeroConnection()
    {
        try {
            // Get the Xero service
            $xeroService = app()->make('xero');
            
            // Try to fetch currencies to verify connection
            $currencies = $xeroService->getCurrencies();
            
            // If successful, return success message
            return redirect()->back()->with('success', 'Successfully connected to Xero. Found ' . count($currencies['Currencies'] ?? []) . ' currencies.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
        }
    }
} 