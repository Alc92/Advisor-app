<?php

declare(strict_types=1);

namespace App\Advisor\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ApproximateDate
{
    public function __construct(private int $year, private int $month)
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('ApproximateDate month must be between 1 and 12.');
        }
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }
}
