<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\Contracts\ExchangeRateProvider;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private array $providers = [];
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct()
    {
        // Register providers in the constructor
        // This will be configured in the service provider
    }

    public function registerProvider(ExchangeRateProvider $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Get the exchange rate for a specific date
     */
    public function getRateForDate(\DateTime $date): ?ExchangeRate
    {
        // Try to get from cache first
        $cacheKey = "exchange_rate_{$date->format('Y-m-d')}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Try to get from database
        $rate = ExchangeRate::getRateForDate($date);
        if ($rate) {
            Cache::put($cacheKey, $rate, self::CACHE_TTL);
            return $rate;
        }

        // Try to fetch from providers
        foreach ($this->providers as $provider) {
            try {
                $rate = $provider->getRateForDate($date);
                if ($rate) {
                    $exchangeRate = ExchangeRate::create([
                        'rate' => $rate,
                        'date' => $date,
                        'source' => 'api',
                        'api_provider' => $provider->getName(),
                        'metadata' => ['fetched_at' => now()]
                    ]);

                    Cache::put($cacheKey, $exchangeRate, self::CACHE_TTL);
                    return $exchangeRate;
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch exchange rate from {$provider->getName()}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Get the latest exchange rate
     */
    public function getLatestRate(): ?ExchangeRate
    {
        // Try to get from cache first
        $cacheKey = 'latest_exchange_rate';
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Try to get from database
        $rate = ExchangeRate::getLatestRate();
        if ($rate) {
            Cache::put($cacheKey, $rate, self::CACHE_TTL);
            return $rate;
        }

        // Try to fetch from providers
        foreach ($this->providers as $provider) {
            try {
                $rate = $provider->getLatestRate();
                if ($rate) {
                    $exchangeRate = ExchangeRate::create([
                        'rate' => $rate,
                        'date' => now(),
                        'source' => 'api',
                        'api_provider' => $provider->getName(),
                        'metadata' => ['fetched_at' => now()]
                    ]);

                    Cache::put($cacheKey, $exchangeRate, self::CACHE_TTL);
                    return $exchangeRate;
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch latest exchange rate from {$provider->getName()}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Manually create an exchange rate
     */
    public function createManualRate(float $rate, \DateTime $date): ExchangeRate
    {
        return ExchangeRate::create([
            'rate' => $rate,
            'date' => $date,
            'source' => 'manual',
            'metadata' => ['created_by' => Auth::id()]
        ]);
    }

    /**
     * Convert USD to COP using the latest rate
     */
    public function convertUsdToCop(float $usdAmount, ?\DateTime $date = null): ?float
    {
        $rate = $date ? $this->getRateForDate($date) : $this->getLatestRate();
        return $rate ? $rate->convertUsdToCop($usdAmount) : null;
    }

    /**
     * Convert COP to USD using the latest rate
     */
    public function convertCopToUsd(float $copAmount, ?\DateTime $date = null): ?float
    {
        $rate = $date ? $this->getRateForDate($date) : $this->getLatestRate();
        return $rate ? $rate->convertCopToUsd($copAmount) : null;
    }
} 