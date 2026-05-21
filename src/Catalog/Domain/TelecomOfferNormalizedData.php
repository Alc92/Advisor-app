<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use InvalidArgumentException;

final readonly class TelecomOfferNormalizedData
{
    public function __construct(
        private ?FiberCapacityBand $fiberCapacityBand,
        private ?MobileUsageBand $mobileUsageBandSupported,
        private int $mobileLinesIncluded,
        private bool $fiberIncluded,
        private bool $tvIncluded,
        private bool $asymmetricLines,
    ) {
        if ($mobileLinesIncluded < 0) {
            throw new InvalidArgumentException('Mobile lines included must be >= 0.');
        }

        if ($fiberIncluded === false && $fiberCapacityBand !== null) {
            throw new InvalidArgumentException('Fiber capacity band must be null when fiber is not included.');
        }

        if ($mobileLinesIncluded === 0 && $mobileUsageBandSupported !== null) {
            throw new InvalidArgumentException('Mobile usage band must be null when no mobile lines are included.');
        }

        if ($asymmetricLines === true && $mobileLinesIncluded <= 1) {
            throw new InvalidArgumentException('Asymmetric lines requires more than 1 mobile line.');
        }
    }

    public function fiberCapacityBand(): ?FiberCapacityBand
    {
        return $this->fiberCapacityBand;
    }

    public function mobileUsageBandSupported(): ?MobileUsageBand
    {
        return $this->mobileUsageBandSupported;
    }

    public function mobileLinesIncluded(): int
    {
        return $this->mobileLinesIncluded;
    }

    public function fiberIncluded(): bool
    {
        return $this->fiberIncluded;
    }

    public function tvIncluded(): bool
    {
        return $this->tvIncluded;
    }

    public function asymmetricLines(): bool
    {
        return $this->asymmetricLines;
    }
}
