<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\ValueObject\Money;

final readonly class InputEvidenceSnapshot
{
    public function __construct(
        private ProductType $productType,
        private Money $approxMonthlyPrice,
        private ?MobileUsageBand $mobileUsageBand,
        private ?FiberNeedBand $fiberNeedBand,
        private CommitmentStatus $commitmentStatus,
        private PromotionStatus $promotionStatus,
        private UserPreference $userPreference,
        private bool $additionalConditionProfileUsed,
    ) {
    }

    public function productType(): ProductType
    {
        return $this->productType;
    }

    public function approxMonthlyPrice(): Money
    {
        return $this->approxMonthlyPrice;
    }

    public function mobileUsageBand(): ?MobileUsageBand
    {
        return $this->mobileUsageBand;
    }

    public function fiberNeedBand(): ?FiberNeedBand
    {
        return $this->fiberNeedBand;
    }

    public function commitmentStatus(): CommitmentStatus
    {
        return $this->commitmentStatus;
    }

    public function promotionStatus(): PromotionStatus
    {
        return $this->promotionStatus;
    }

    public function userPreference(): UserPreference
    {
        return $this->userPreference;
    }

    public function additionalConditionProfileUsed(): bool
    {
        return $this->additionalConditionProfileUsed;
    }
}
