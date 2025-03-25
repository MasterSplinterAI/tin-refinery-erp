<?php

namespace App\Domain\ExchangeRate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate',
        'date',
        'source',
        'api_provider',
        'metadata'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'date' => 'date',
        'metadata' => 'array'
    ];

    /**
     * Get the exchange rate for a specific date
     */
    public static function getRateForDate(\DateTime|string $date): ?self
    {
        $date = is_string($date) ? new \DateTime($date) : $date;
        return static::where('date', $date->format('Y-m-d'))->first();
    }

    /**
     * Get the most recent exchange rate
     */
    public static function getLatestRate(): ?self
    {
        return static::orderBy('date', 'desc')->first();
    }

    /**
     * Convert USD to COP
     */
    public function convertUsdToCop(float $usdAmount): float
    {
        return $usdAmount * $this->rate;
    }

    /**
     * Convert COP to USD
     */
    public function convertCopToUsd(float $copAmount): float
    {
        return $copAmount / $this->rate;
    }
} 