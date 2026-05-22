<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\UncertaintyFlag;

final class ChangeFrictionCalculator
{
    public function calculate(
        CurrentSituation $currentSituation,
        InputQuality $inputQuality,
    ): ChangeFriction {
        if ($currentSituation->commitmentStatus() === CommitmentStatus::YES) {
            return ChangeFriction::HIGH;
        }

        if ($currentSituation->commitmentStatus() === CommitmentStatus::UNKNOWN) {
            return ChangeFriction::MEDIUM;
        }

        if (
            $inputQuality->hasUncertainty(UncertaintyFlag::UNKNOWN_COMMITMENT)
            || $inputQuality->hasUncertainty(UncertaintyFlag::UNKNOWN_PROMOTION)
            || $inputQuality->hasUncertainty(UncertaintyFlag::MULTI_RESIDENCE)
            || $inputQuality->hasUncertainty(UncertaintyFlag::SIMPLIFIED_MODEL_LIMITATION)
        ) {
            return ChangeFriction::MEDIUM;
        }

        return ChangeFriction::LOW;
    }
}
