<?php

namespace App\Domain\Inventory\Events;

use App\Domain\Common\Events\DomainEvent;

class InventoryQuantityChanged extends DomainEvent
{
    public function __construct(array $inventoryData)
    {
        parent::__construct($inventoryData);
    }

    public function getInventoryData(): array
    {
        return $this->data;
    }
} 