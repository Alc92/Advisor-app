<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\AdditionalConditionProfile;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\FiberSpeedBandCurrent;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\TriState;
use App\Advisor\Domain\Enum\UserPreference;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use App\Catalog\Domain\TelecomOfferNormalizedData;

final class FitCalculator
{
    public function calculate(
        CurrentSituation $currentSituation,
        UserPreference $userPreference,
        ?AdditionalConditionProfile $additionalConditionProfile,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        $productType = $currentSituation->productType();

        if ($productType === ProductType::MOBILE) {
            $level = $this->calculateMobileFit($currentSituation, $offerNormalizedData);
        } elseif ($productType === ProductType::FIBER) {
            $level = $this->calculateFiberFit($currentSituation, $offerNormalizedData);
        } else {
            $level = $this->calculateFiberMobileFit($currentSituation, $offerNormalizedData);
        }

        $level = $this->applyAsymmetricPenalty($level, $currentSituation, $offerNormalizedData);
        $level = $this->applyTvPenalty($level, $currentSituation, $userPreference, $additionalConditionProfile, $offerNormalizedData);
        $level = $this->applyKeepConditionsFiberPenalty($level, $currentSituation, $userPreference, $additionalConditionProfile, $offerNormalizedData);

        return $level;
    }

    private function calculateMobileFit(
        CurrentSituation $currentSituation,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($offerNormalizedData->mobileLinesIncluded() <= 0) {
            return FitLevel::LOW;
        }

        $need = $currentSituation->mobileUsageBand();

        if ($need === null) {
            return FitLevel::MEDIUM;
        }

        $capacity = $offerNormalizedData->mobileUsageBandSupported();

        if ($capacity === null) {
            return FitLevel::MEDIUM;
        }

        if ($this->mobileBandRank($capacity) < $this->mobileBandRank($need)) {
            return FitLevel::LOW;
        }

        return FitLevel::HIGH;
    }

    private function calculateFiberFit(
        CurrentSituation $currentSituation,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($offerNormalizedData->fiberIncluded() === false) {
            return FitLevel::LOW;
        }

        $need = $currentSituation->fiberNeedBand();

        if ($need === null) {
            return FitLevel::MEDIUM;
        }

        $capacity = $offerNormalizedData->fiberCapacityBand();

        if ($capacity === null) {
            return FitLevel::MEDIUM;
        }

        if ($this->fiberBandRank($capacity) < $this->fiberBandRank($need)) {
            return FitLevel::LOW;
        }

        return FitLevel::HIGH;
    }

    private function calculateFiberMobileFit(
        CurrentSituation $currentSituation,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($offerNormalizedData->fiberIncluded() === false) {
            return FitLevel::LOW;
        }

        if ($offerNormalizedData->mobileLinesIncluded() <= 0) {
            return FitLevel::LOW;
        }

        $mobileNeed = $currentSituation->mobileUsageBand();
        $fiberNeed = $currentSituation->fiberNeedBand();
        $mobileCapacity = $offerNormalizedData->mobileUsageBandSupported();
        $fiberCapacity = $offerNormalizedData->fiberCapacityBand();

        $hasExplicitMobileDeficit = $mobileNeed !== null && $mobileCapacity !== null
            && $this->mobileBandRank($mobileCapacity) < $this->mobileBandRank($mobileNeed);

        $hasExplicitFiberDeficit = $fiberNeed !== null && $fiberCapacity !== null
            && $this->fiberBandRank($fiberCapacity) < $this->fiberBandRank($fiberNeed);

        if ($hasExplicitMobileDeficit || $hasExplicitFiberDeficit) {
            return FitLevel::LOW;
        }

        $hasMissingMobileData = $mobileNeed === null || $mobileCapacity === null;
        $hasMissingFiberData = $fiberNeed === null || $fiberCapacity === null;

        if ($hasMissingMobileData || $hasMissingFiberData) {
            return FitLevel::MEDIUM;
        }

        return FitLevel::HIGH;
    }

    private function applyAsymmetricPenalty(
        FitLevel $level,
        CurrentSituation $currentSituation,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($offerNormalizedData->asymmetricLines() === true && $currentSituation->hasMobileComponent()) {
            return $this->capAtMedium($level);
        }

        return $level;
    }

    private function applyTvPenalty(
        FitLevel $level,
        CurrentSituation $currentSituation,
        UserPreference $userPreference,
        ?AdditionalConditionProfile $additionalConditionProfile,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($offerNormalizedData->tvIncluded() === false) {
            $userHasTv = $currentSituation->tvIncluded() === true;
            $tvImportant = $additionalConditionProfile !== null
                && $additionalConditionProfile->tvImportance() === TriState::YES;

            if ($userHasTv || $tvImportant) {
                if ($userPreference === UserPreference::KEEP_CONDITIONS) {
                    return $this->minLevel($level, FitLevel::LOW);
                }

                return $this->capAtMedium($level);
            }
        }

        return $level;
    }

    private function applyKeepConditionsFiberPenalty(
        FitLevel $level,
        CurrentSituation $currentSituation,
        UserPreference $userPreference,
        ?AdditionalConditionProfile $additionalConditionProfile,
        TelecomOfferNormalizedData $offerNormalizedData,
    ): FitLevel {
        if ($userPreference !== UserPreference::KEEP_CONDITIONS) {
            return $level;
        }

        if ($additionalConditionProfile === null) {
            return $level;
        }

        $fiberSpeedCurrent = $additionalConditionProfile->fiberSpeedBandCurrent();

        if ($fiberSpeedCurrent === null || $fiberSpeedCurrent === FiberSpeedBandCurrent::UNKNOWN) {
            return $level;
        }

        if (!$currentSituation->hasFiberComponent()) {
            return $level;
        }

        $offerCapacity = $offerNormalizedData->fiberCapacityBand();

        if ($offerCapacity === null) {
            return $level;
        }

        $currentCapacityRank = $this->fiberSpeedBandToCapacityRank($fiberSpeedCurrent);
        $offerCapacityRank = $this->fiberBandRank($offerCapacity);

        if ($offerCapacityRank < $currentCapacityRank) {
            return $this->minLevel($level, FitLevel::LOW);
        }

        return $level;
    }

    private function capAtMedium(FitLevel $level): FitLevel
    {
        if ($level === FitLevel::HIGH) {
            return FitLevel::MEDIUM;
        }

        return $level;
    }

    private function minLevel(FitLevel $a, FitLevel $b): FitLevel
    {
        $rank = [
            FitLevel::HIGH->value => 3,
            FitLevel::MEDIUM->value => 2,
            FitLevel::LOW->value => 1,
        ];

        $rankA = $rank[$a->value] ?? 0;
        $rankB = $rank[$b->value] ?? 0;

        return $rankA <= $rankB ? $a : $b;
    }

    private function mobileBandRank(MobileUsageBand $band): int
    {
        return match ($band) {
            MobileUsageBand::LOW => 1,
            MobileUsageBand::MEDIUM => 2,
            MobileUsageBand::HIGH => 3,
        };
    }

    private function fiberBandRank(FiberCapacityBand|FiberNeedBand $band): int
    {
        return match ($band) {
            FiberCapacityBand::BASIC, FiberNeedBand::BASIC => 1,
            FiberCapacityBand::STANDARD, FiberNeedBand::STANDARD => 2,
            FiberCapacityBand::HIGH, FiberNeedBand::HIGH => 3,
        };
    }

    private function fiberSpeedBandToCapacityRank(FiberSpeedBandCurrent $band): int
    {
        return match ($band) {
            FiberSpeedBandCurrent::UP_TO_300_MB => 1,
            FiberSpeedBandCurrent::MBPS_600 => 2,
            FiberSpeedBandCurrent::GBPS_1_OR_MORE => 3,
            FiberSpeedBandCurrent::UNKNOWN => 0,
        };
    }
}
