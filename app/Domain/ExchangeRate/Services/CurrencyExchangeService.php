<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\Models\CurrencyExchange;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\XeroService;
use App\Domain\Common\Models\AccountMapping;

class CurrencyExchangeService
{
    protected $xeroService;

    public function __construct(XeroService $xeroService)
    {
        $this->xeroService = $xeroService;
    }

    /**
     * Create a new currency exchange transaction
     */
    public function createExchange(array $data): CurrencyExchange
    {
        try {
            DB::beginTransaction();

            // Calculate the effective rate before creating the exchange
            if (!isset($data['effective_rate'])) {
                $netCopAmount = $data['cop_amount'] - ($data['bank_fee_cop'] ?? 0);
                $data['effective_rate'] = $netCopAmount / $data['usd_amount'];
            }

            $exchange = CurrencyExchange::create($data);

            // Only attempt Xero integration if the service is available
            if ($this->xeroService) {
                try {
                    $xeroBillId = $this->xeroService->createCurrencyExchange($data);
                    if ($xeroBillId) {
                        Log::info('Xero bill created successfully', [
                            'exchange_id' => $exchange->id,
                            'xero_bill_id' => $xeroBillId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create Xero bill', [
                        'exchange_id' => $exchange->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            Log::info('Currency exchange created successfully', ['exchange_id' => $exchange->id]);

            return $exchange;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create currency exchange', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get all currency exchanges
     */
    public function getExchanges(array $filters = []): LengthAwarePaginator
    {
        $query = CurrencyExchange::query()
            ->orderBy('exchange_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['start_date'])) {
            $query->where('exchange_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('exchange_date', '<=', $filters['end_date']);
        }

        return $query->paginate(10);
    }

    /**
     * Get a summary of currency exchanges
     */
    public function getSummary(array $filters = []): array
    {
        $query = CurrencyExchange::query();

        if (!empty($filters['start_date'])) {
            $query->where('exchange_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('exchange_date', '<=', $filters['end_date']);
        }

        $exchanges = $query->get();

        return [
            'total_usd_exchanged' => $exchanges->sum('usd_amount'),
            'total_cop_received' => $exchanges->sum('cop_amount'),
            'total_bank_fees_usd' => $exchanges->sum('bank_fee_usd'),
            'total_bank_fees_cop' => $exchanges->sum('bank_fee_cop'),
            'average_rate' => $exchanges->avg('exchange_rate'),
            'average_effective_rate' => $exchanges->avg('effective_rate'),
        ];
    }

    /**
     * Retry syncing failed Xero transactions
     */
    public function retryFailedSyncs(): int
    {
        if (!$this->xeroService) {
            Log::warning('Cannot retry Xero syncs: XeroService not available');
            return 0;
        }

        $failedExchanges = CurrencyExchange::where('xero_status', 'failed')->get();
        $successCount = 0;

        foreach ($failedExchanges as $exchange) {
            try {
                $xeroBillId = $this->xeroService->createCurrencyExchange($exchange->toArray());
                if ($xeroBillId) {
                    $successCount++;
                    Log::info('Successfully retried Xero sync', [
                        'exchange_id' => $exchange->id,
                        'xero_bill_id' => $xeroBillId
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to retry Xero sync', [
                    'exchange_id' => $exchange->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $successCount;
    }

    /**
     * Sync a currency exchange to Xero
     *
     * @param array $data The currency exchange data
     * @return array The response from Xero
     */
    public function syncExchangeToXero(array $data): array
    {
        try {
            // Get account mappings
            $usdBankAccount = AccountMapping::getMapping('currency_exchange', 'usd_bank_account');
            $copBankAccount = AccountMapping::getMapping('currency_exchange', 'cop_bank_account');
            $bankFeeAccount = AccountMapping::getMapping('currency_exchange', 'bank_fee_account');

            if (!$usdBankAccount || !$copBankAccount) {
                throw new \Exception('Required account mappings not found');
            }

            // Prepare data for Xero
            $xeroData = [
                'date' => $data['date'],
                'usd_amount' => $data['usd_amount'],
                'cop_amount' => $data['cop_amount'],
                'bank_fee' => $data['bank_fee'] ?? 0,
                'bank_name' => $data['bank_name'] ?? 'Currency Exchange',
                'reference' => $data['reference'] ?? ('FX-' . now()->format('Ymd')),
                'from_account_code' => $usdBankAccount->xero_account_code,
                'to_account_code' => $copBankAccount->xero_account_code,
                'fee_account_code' => $bankFeeAccount ? $bankFeeAccount->xero_account_code : null,
                'bank_account_code' => $usdBankAccount->xero_account_code, // Using USD account as primary
            ];

            // Create the transaction in Xero
            return $this->xeroService->createCurrencyExchange($xeroData);
        } catch (\Exception $e) {
            Log::error('Failed to sync currency exchange to Xero', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
} 