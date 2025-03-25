<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\Models\CurrencyExchange;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    protected $xeroIntegrationService;

    public function __construct(XeroIntegrationService $xeroIntegrationService = null)
    {
        $this->xeroIntegrationService = $xeroIntegrationService;
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
            if ($this->xeroIntegrationService) {
                try {
                    $xeroBillId = $this->xeroIntegrationService->createBill($exchange);
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
        if (!$this->xeroIntegrationService) {
            Log::warning('Cannot retry Xero syncs: XeroIntegrationService not available');
            return 0;
        }

        $failedExchanges = CurrencyExchange::where('xero_status', 'failed')->get();
        $successCount = 0;

        foreach ($failedExchanges as $exchange) {
            try {
                $xeroBillId = $this->xeroIntegrationService->createBill($exchange);
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
} 