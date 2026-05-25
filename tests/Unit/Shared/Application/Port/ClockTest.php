<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port;

use App\Shared\Application\Port\Clock;
use App\Tests\Support\Shared\FixedClock;
use PHPUnit\Framework\TestCase;

final class ClockTest extends TestCase
{
    public function test_fixed_clock_implements_clock_contract(): void
    {
        $clock = new FixedClock(new \DateTimeImmutable('2026-01-01 10:00:00'));

        self::assertInstanceOf(Clock::class, $clock);
    }

    public function test_fixed_clock_returns_configured_date_time(): void
    {
        $configuredNow = new \DateTimeImmutable('2026-01-01 10:00:00');
        $clock = new FixedClock($configuredNow);

        self::assertSame($configuredNow, $clock->now());
    }
}
