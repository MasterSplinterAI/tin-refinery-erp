<?php

namespace App\Providers;

use App\Domain\ExchangeRate\Services\ExchangeRateService;
use Illuminate\Support\ServiceProvider;

class ExchangeRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExchangeRateService::class, function ($app) {
            $service = new ExchangeRateService();
            
            // Register exchange rate providers here
            // Example:
            // $service->registerProvider(new OpenExchangeRatesProvider());
            
            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
} 