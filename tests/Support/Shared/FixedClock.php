<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared;

use App\Shared\Application\Port\Clock;

final class FixedClock implements Clock
{
    public function __construct(private readonly \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
