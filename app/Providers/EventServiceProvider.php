<?php

namespace App\Providers;

use App\Domain\Common\Events\DomainEvent;
use App\Domain\Inventory\Events\InventoryTransactionCreated;
use App\Domain\Inventory\Events\InventoryTransactionReversed;
use App\Domain\Inventory\Events\InventoryQuantityChanged;
use App\Domain\Inventory\Events\InventoryAdjustmentCreated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Log all domain events
        Event::listen(DomainEvent::class, function ($event) {
            Log::info('Domain Event: ' . get_class($event), [
                'event_data' => $event->getData(),
                'timestamp' => $event->getTimestamp()
            ]);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 