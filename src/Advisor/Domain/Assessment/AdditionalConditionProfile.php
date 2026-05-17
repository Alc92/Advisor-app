<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\FiberSpeedBandCurrent;
use App\Advisor\Domain\Enum\MobileDataAdequacyLevel;
use App\Advisor\Domain\Enum\MobileUsageDistribution;
use App\Advisor\Domain\Enum\TriState;

final readonly class AdditionalConditionProfile
{
    public function __construct(
        private ?FiberSpeedBandCurrent $fiberSpeedBandCurrent,
        private ?MobileDataAdequacyLevel $mobileDataAdequacyLevel,
        private ?MobileUsageDistribution $mobileUsageDistribution,
        private ?TriState $tvImportance,
    ) {
    }

    public function fiberSpeedBandCurrent(): ?FiberSpeedBandCurrent
    {
        return $this->fiberSpeedBandCurrent;
    }

    public function mobileDataAdequacyLevel(): ?MobileDataAdequacyLevel
    {
        return $this->mobileDataAdequacyLevel;
    }

    public function mobileUsageDistribution(): ?MobileUsageDistribution
    {
        return $this->mobileUsageDistribution;
    }

    public function tvImportance(): ?TriState
    {
        return $this->tvImportance;
    }

    public function isEmpty(): bool
    {
        return $this->fiberSpeedBandCurrent === null
            && $this->mobileDataAdequacyLevel === null
            && $this->mobileUsageDistribution === null
            && $this->tvImportance === null;
    }
}
