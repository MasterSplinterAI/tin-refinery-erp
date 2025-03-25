<?php

namespace App\Domain\ExchangeRate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyExchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_date',
        'usd_amount',
        'cop_amount',
        'exchange_rate',
        'effective_rate',
        'bank_fee_usd',
        'bank_fee_cop',
        'bank_name',
        'bank_reference',
        'notes',
        'xero_reference',
        'xero_bill_id',
        'xero_status',
        'xero_currency_rate',
        'xero_synced',
        'xero_sync_date',
        'xero_sync_error',
    ];

    protected $casts = [
        'exchange_date' => 'date',
        'usd_amount' => 'decimal:2',
        'cop_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'effective_rate' => 'decimal:4',
        'bank_fee_usd' => 'decimal:2',
        'bank_fee_cop' => 'decimal:2',
        'xero_synced' => 'boolean',
        'xero_sync_date' => 'datetime',
    ];

    /**
     * Calculate and set the effective exchange rate before saving
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($exchange) {
            if (!isset($exchange->effective_rate)) {
                $exchange->effective_rate = $exchange->getEffectiveRate();
            }
        });

        static::updating(function ($exchange) {
            if ($exchange->isDirty(['usd_amount', 'cop_amount', 'bank_fee_usd', 'bank_fee_cop'])) {
                $exchange->effective_rate = $exchange->getEffectiveRate();
            }
        });
    }

    /**
     * Calculate the effective exchange rate (COP received / USD paid)
     */
    public function getEffectiveRate()
    {
        // Total COP received minus fees divided by total USD spent
        $netCopAmount = $this->cop_amount - $this->bank_fee_cop;
        $totalUsdAmount = $this->usd_amount;
        
        if ($totalUsdAmount <= 0) {
            return 0;
        }
        
        return $netCopAmount / $totalUsdAmount;
    }

    /**
     * Get the total USD amount including fees
     */
    public function getTotalUsdAmount()
    {
        return $this->usd_amount + $this->bank_fee_usd;
    }

    /**
     * Get the net COP amount after deducting fees
     */
    public function getNetCopAmount()
    {
        return $this->cop_amount - $this->bank_fee_cop;
    }
} 