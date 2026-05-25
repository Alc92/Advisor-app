<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
