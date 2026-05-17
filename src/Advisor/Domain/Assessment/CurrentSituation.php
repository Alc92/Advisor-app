<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use App\Advisor\Domain\ValueObject\Money;
use InvalidArgumentException;

final readonly class CurrentSituation
{
    private string $currentProvider;

    public function __construct(
        string $currentProvider,
        private ProductType $productType,
        private Money $approxMonthlyPrice,
        private ?int $mobileLinesCount,
        private ?MobileUsageBand $mobileUsageBand,
        private ?FiberNeedBand $fiberNeedBand,
        private CommitmentStatus $commitmentStatus,
        private ?ApproximateDate $commitmentEndApprox,
        private PromotionStatus $promotionStatus,
        private ?ApproximateDate $promotionEndApprox,
        private ?bool $tvIncluded,
        private bool $multipleResidencesDetected,
        private DataProvenance $dataProvenance,
    ) {
        $trimmedProvider = trim($currentProvider);

        if ($trimmedProvider === '') {
            throw new InvalidArgumentException('Current provider cannot be empty.');
        }

        $this->currentProvider = $trimmedProvider;

        if ($mobileLinesCount !== null && $mobileLinesCount <= 0) {
            throw new InvalidArgumentException('Mobile lines count must be greater than 0.');
        }

        if ($productType === ProductType::MOBILE && $fiberNeedBand !== null) {
            throw new InvalidArgumentException('MOBILE product type cannot have fiber component.');
        }

        if ($productType === ProductType::FIBER && $mobileLinesCount !== null) {
            throw new InvalidArgumentException('FIBER product type cannot have mobile lines.');
        }

        if ($productType === ProductType::FIBER && $mobileUsageBand !== null) {
            throw new InvalidArgumentException('FIBER product type cannot have mobile usage band.');
        }

        if ($commitmentStatus !== CommitmentStatus::YES && $commitmentEndApprox !== null) {
            throw new InvalidArgumentException('Commitment end date requires commitment status YES.');
        }

        if ($promotionStatus !== PromotionStatus::ACTIVE && $promotionEndApprox !== null) {
            throw new InvalidArgumentException('Promotion end date requires promotion status ACTIVE.');
        }
    }

    public function currentProvider(): string
    {
        return $this->currentProvider;
    }

    public function productType(): ProductType
    {
        return $this->productType;
    }

    public function approxMonthlyPrice(): Money
    {
        return $this->approxMonthlyPrice;
    }

    public function mobileLinesCount(): ?int
    {
        return $this->mobileLinesCount;
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

    public function commitmentEndApprox(): ?ApproximateDate
    {
        return $this->commitmentEndApprox;
    }

    public function promotionStatus(): PromotionStatus
    {
        return $this->promotionStatus;
    }

    public function promotionEndApprox(): ?ApproximateDate
    {
        return $this->promotionEndApprox;
    }

    public function tvIncluded(): ?bool
    {
        return $this->tvIncluded;
    }

    public function multipleResidencesDetected(): bool
    {
        return $this->multipleResidencesDetected;
    }

    public function dataProvenance(): DataProvenance
    {
        return $this->dataProvenance;
    }

    public function hasMobileComponent(): bool
    {
        return $this->productType === ProductType::MOBILE
            || $this->productType === ProductType::FIBER_MOBILE;
    }

    public function hasFiberComponent(): bool
    {
        return $this->productType === ProductType::FIBER
            || $this->productType === ProductType::FIBER_MOBILE;
    }

    public function hasKnownCommitmentEnd(): bool
    {
        return $this->commitmentEndApprox !== null;
    }

    public function hasKnownPromotionEnd(): bool
    {
        return $this->promotionEndApprox !== null;
    }
}
