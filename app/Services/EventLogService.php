<?php

namespace App\Services;

use App\Domain\Common\Events\DomainEvent;
use App\Models\EventLog;
use Illuminate\Support\Facades\Auth;

class EventLogService
{
    public function logEvent(DomainEvent $event)
    {
        return EventLog::create([
            'event_type' => get_class($event),
            'event_data' => $event->getData(),
            'occurred_at' => $event->getTimestamp(),
            'user_id' => Auth::id()
        ]);
    }
} 