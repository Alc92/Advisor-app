<?php

declare(strict_types=1);

namespace App\Advisor\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Money
{
    private string $amount;
    private string $currency;

    public function __construct(string $amount, string $currency)
    {
        $trimmedAmount = trim($amount);

        if (!is_numeric($trimmedAmount)) {
            throw new InvalidArgumentException('Money amount must be numeric.');
        }

        if ((float) $trimmedAmount <= 0) {
            throw new InvalidArgumentException('Money amount must be greater than 0.');
        }

        $trimmedCurrency = trim($currency);

        if ($trimmedCurrency === '') {
            throw new InvalidArgumentException('Money currency cannot be empty.');
        }

        if ($trimmedCurrency !== 'EUR') {
            throw new InvalidArgumentException('Money currency must be EUR in MVP.');
        }

        $this->amount = $trimmedAmount;
        $this->currency = $trimmedCurrency;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
