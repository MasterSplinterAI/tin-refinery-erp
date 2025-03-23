<?php

namespace App\Domain\Inventory\Providers;

use App\Domain\Inventory\Repositories\InventoryRepository;
use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
    }

    public function boot()
    {
        //
    }
} 