<?php

namespace App\Domain\Inventory\Events;

use App\Domain\Common\Events\DomainEvent;

class InventoryTransactionReversed extends DomainEvent
{
    public function __construct(array $transactionData)
    {
        parent::__construct($transactionData);
    }

    public function getTransactionData(): array
    {
        return $this->data;
    }
} 