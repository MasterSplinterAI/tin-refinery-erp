<?php

namespace App\Domain\Common\Events;

abstract class DomainEvent
{
    public $timestamp;
    public $data;

    public function __construct(array $data = [])
    {
        $this->timestamp = now();
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
} 