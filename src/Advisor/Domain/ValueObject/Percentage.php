<?php

declare(strict_types=1);

namespace App\Advisor\Domain\ValueObject;

final readonly class Percentage
{
    public function __construct(private string|float $value)
    {
    }

    public function value(): string|float
    {
        return $this->value;
    }
}
