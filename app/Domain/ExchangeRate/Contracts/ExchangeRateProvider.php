<?php

namespace App\Domain\ExchangeRate\Contracts;

interface ExchangeRateProvider
{
    /**
     * Get the exchange rate for a specific date
     */
    public function getRateForDate(\DateTime $date): ?float;

    /**
     * Get the latest exchange rate
     */
    public function getLatestRate(): ?float;

    /**
     * Get the provider name
     */
    public function getName(): string;
} 