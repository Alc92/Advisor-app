<?php

declare(strict_types=1);

namespace App\Advisor\Application\Service;

use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use InvalidArgumentException;

final readonly class EvaluablePublishedOffer
{
    private string $sourceOfferVersionId;
    private string $provider;
    private string $commercialName;

    public function __construct(
        string $sourceOfferVersionId,
        private TelecomOfferVersionId $stableInternalOfferVersionId,
        string $provider,
        string $commercialName,
        private ProductType $productType,
        private Money $monthlyPrice,
        private ?int $fiberSpeedMbps,
        private ?string $mobileDataDisplay,
        private int $mobileLinesIncluded,
        private bool $tvIncluded,
        private ?FiberCapacityBand $fiberCapacityBand,
        private ?MobileUsageBand $mobileUsageBandSupported,
        private bool $fiberIncluded,
        private bool $asymmetricLines,
    ) {
        if (trim($sourceOfferVersionId) === '') {
            throw new InvalidArgumentException('sourceOfferVersionId cannot be empty.');
        }

        if (trim($provider) === '') {
            throw new InvalidArgumentException('provider cannot be empty.');
        }

        if (trim($commercialName) === '') {
            throw new InvalidArgumentException('commercialName cannot be empty.');
        }

        if ($mobileLinesIncluded < 0) {
            throw new InvalidArgumentException('mobileLinesIncluded must be greater than or equal to 0.');
        }

        $this->sourceOfferVersionId = $sourceOfferVersionId;
        $this->provider = $provider;
        $this->commercialName = $commercialName;
    }

    public function sourceOfferVersionId(): string
    {
        return $this->sourceOfferVersionId;
    }

    public function stableInternalOfferVersionId(): TelecomOfferVersionId
    {
        return $this->stableInternalOfferVersionId;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function commercialName(): string
    {
        return $this->commercialName;
    }

    public function productType(): ProductType
    {
        return $this->productType;
    }

    public function monthlyPrice(): Money
    {
        return $this->monthlyPrice;
    }

    public function fiberSpeedMbps(): ?int
    {
        return $this->fiberSpeedMbps;
    }

    public function mobileDataDisplay(): ?string
    {
        return $this->mobileDataDisplay;
    }

    public function mobileLinesIncluded(): int
    {
        return $this->mobileLinesIncluded;
    }

    public function tvIncluded(): bool
    {
        return $this->tvIncluded;
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
