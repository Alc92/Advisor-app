<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\CompletenessLevel;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\UncertaintyFlag;
use InvalidArgumentException;

final readonly class InputQuality
{
    /**
     * @var list<UncertaintyFlag>
     */
    private array $uncertaintyFlags;

    /**
     * @param list<UncertaintyFlag> $uncertaintyFlags
     */
    public function __construct(
        private CompletenessLevel $completenessLevel,
        array $uncertaintyFlags,
    ) {
        foreach ($uncertaintyFlags as $flag) {
            if (!$flag instanceof UncertaintyFlag) {
                throw new InvalidArgumentException('All uncertainty flags must be instances of UncertaintyFlag.');
            }
        }

        $uniqueFlags = [];
        foreach ($uncertaintyFlags as $flag) {
            $key = $flag->value;
            if (isset($uniqueFlags[$key])) {
                throw new InvalidArgumentException('Duplicate uncertainty flag: ' . $flag->value);
            }
            $uniqueFlags[$key] = $flag;
        }

        $this->uncertaintyFlags = array_values($uniqueFlags);
    }

    public function completenessLevel(): CompletenessLevel
    {
        return $this->completenessLevel;
    }

    /**
     * @return list<UncertaintyFlag>
     */
    public function uncertaintyFlags(): array
    {
        return $this->uncertaintyFlags;
    }

    public function allowsCatalogEvaluation(): bool
    {
        return $this->completenessLevel !== CompletenessLevel::MINIMUM_NOT_MET;
    }

    public function hasUncertainty(UncertaintyFlag $flag): bool
    {
        foreach ($this->uncertaintyFlags as $existingFlag) {
            if ($existingFlag === $flag) {
                return true;
            }
        }

        return false;
    }

    public static function fromCurrentSituation(
        CurrentSituation $currentSituation,
        ?AdditionalConditionProfile $additionalConditionProfile = null,
    ): self {
        $uncertaintyFlags = [];
        $minimumMet = true;

        $productType = $currentSituation->productType();

        if ($productType === ProductType::MOBILE) {
            if ($currentSituation->mobileUsageBand() === null) {
                $minimumMet = false;
                $uncertaintyFlags[] = UncertaintyFlag::MISSING_USAGE;
            }
        } elseif ($productType === ProductType::FIBER) {
            if ($currentSituation->fiberNeedBand() === null) {
                $minimumMet = false;
                $uncertaintyFlags[] = UncertaintyFlag::MISSING_USAGE;
            }
        } elseif ($productType === ProductType::FIBER_MOBILE) {
            if ($currentSituation->mobileUsageBand() === null || $currentSituation->fiberNeedBand() === null) {
                $minimumMet = false;
                $uncertaintyFlags[] = UncertaintyFlag::MISSING_USAGE;
            }
        }

        if ($currentSituation->commitmentStatus() === CommitmentStatus::UNKNOWN) {
            $uncertaintyFlags[] = UncertaintyFlag::UNKNOWN_COMMITMENT;
        }

        if ($currentSituation->promotionStatus() === PromotionStatus::UNKNOWN) {
            $uncertaintyFlags[] = UncertaintyFlag::UNKNOWN_PROMOTION;
        }

        if ($currentSituation->tvIncluded() === null) {
            $uncertaintyFlags[] = UncertaintyFlag::UNKNOWN_TV;
        }

        if ($currentSituation->multipleResidencesDetected()) {
            $uncertaintyFlags[] = UncertaintyFlag::MULTI_RESIDENCE;
        }

        $completenessLevel = $minimumMet
            ? CompletenessLevel::MINIMUM_MET
            : CompletenessLevel::MINIMUM_NOT_MET;

        if ($minimumMet && $additionalConditionProfile !== null && !$additionalConditionProfile->isEmpty()) {
            $completenessLevel = CompletenessLevel::RICH;
        }

        return new self($completenessLevel, $uncertaintyFlags);
    }
}
