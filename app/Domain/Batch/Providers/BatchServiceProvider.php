<?php

namespace App\Domain\Batch\Providers;

use App\Domain\Batch\Services\BatchService;
use Illuminate\Support\ServiceProvider;

class BatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BatchService::class);
    }

    public function boot(): void
    {
        //
    }
} 