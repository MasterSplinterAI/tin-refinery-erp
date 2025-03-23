<?php

namespace App\Providers;

use App\Services\BatchService;
use App\Services\InventoryTransactionService;
use App\Services\ProcessService;
use Illuminate\Support\ServiceProvider;

class BatchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ProcessService::class);
        $this->app->singleton(InventoryTransactionService::class);
        
        $this->app->singleton(BatchService::class, function ($app) {
            return new BatchService(
                $app->make(InventoryTransactionService::class),
                $app->make(ProcessService::class)
            );
        });
    }
} 