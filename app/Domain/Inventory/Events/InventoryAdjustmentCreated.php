<?php

namespace App\Domain\Inventory\Events;

use App\Domain\Common\Events\DomainEvent;

class InventoryAdjustmentCreated extends DomainEvent
{
    public function __construct(array $adjustmentData)
    {
        parent::__construct($adjustmentData);
    }

    public function getAdjustmentData(): array
    {
        return $this->data;
    }
} 