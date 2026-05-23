<?php

declare(strict_types=1);

namespace App\Advisor\Application\Port;

use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use InvalidArgumentException;

final readonly class PublishedOfferVersionForEvaluation
{
    private string $provider;
    private string $commercialName;
    private ?string $mobileDataDisplay;
    private string $offerVersionId;

    public function __construct(
        string $offerVersionId,
        string $provider,
        string $commercialName,
        private Money $monthlyPrice,
        private int $mobileLinesIncluded,
        private bool $tvIncluded,
        private ?int $fiberSpeedMbps,
        ?string $mobileDataDisplay,
        private ProductType $productType,
        private ?FiberCapacityBand $fiberCapacityBand,
        private ?MobileUsageBand $mobileUsageBandSupported,
        private bool $fiberIncluded,
        private bool $asymmetricLines,
    ) {
        $trimmedOfferVersionId = trim($offerVersionId);
        if ($trimmedOfferVersionId === '') {
            throw new InvalidArgumentException('offerVersionId cannot be empty.');
        }

        $trimmedProvider = trim($provider);
        if ($trimmedProvider === '') {
            throw new InvalidArgumentException('provider cannot be empty.');
        }

        $trimmedCommercialName = trim($commercialName);
        if ($trimmedCommercialName === '') {
            throw new InvalidArgumentException('commercialName cannot be empty.');
        }

        if ($this->mobileLinesIncluded < 0) {
            throw new InvalidArgumentException('mobileLinesIncluded must be greater than or equal to 0.');
        }

        if ($this->fiberSpeedMbps !== null && $this->fiberSpeedMbps <= 0) {
            throw new InvalidArgumentException('fiberSpeedMbps must be greater than 0 when provided.');
        }

        $this->offerVersionId = $trimmedOfferVersionId;
        $this->provider = $trimmedProvider;
        $this->commercialName = $trimmedCommercialName;
        $this->mobileDataDisplay = $mobileDataDisplay !== null ? trim($mobileDataDisplay) : null;
    }

    public function offerVersionId(): string
    {
        return $this->offerVersionId;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function commercialName(): string
    {
        return $this->commercialName;
    }

    public function monthlyPrice(): Money
    {
        return $this->monthlyPrice;
    }

    public function mobileLinesIncluded(): int
    {
        return $this->mobileLinesIncluded;
    }

    public function tvIncluded(): bool
    {
        return $this->tvIncluded;
    }

    public function fiberSpeedMbps(): ?int
    {
        return $this->fiberSpeedMbps;
    }

    public function mobileDataDisplay(): ?string
    {
        return $this->mobileDataDisplay;
    }

    public function productType(): ProductType
    {
        return $this->productType;
    }

    public function fiberCapacityBand(): ?FiberCapacityBand
    {
        return $this->fiberCapacityBand;
    }

    public function mobileUsageBandSupported(): ?MobileUsageBand
    {
        return $this->mobileUsageBandSupported;
    }

    public function fiberIncluded(): bool
    {
        return $this->fiberIncluded;
    }

    public function asymmetricLines(): bool
    {
        return $this->asymmetricLines;
    }
}
