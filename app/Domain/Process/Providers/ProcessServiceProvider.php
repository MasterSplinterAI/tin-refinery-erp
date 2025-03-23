<?php

namespace App\Domain\Process\Providers;

use App\Domain\Process\Services\ProcessService;
use Illuminate\Support\ServiceProvider;

class ProcessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProcessService::class);
    }

    public function boot(): void
    {
        //
    }
} 