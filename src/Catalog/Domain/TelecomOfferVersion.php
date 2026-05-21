<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Advisor\Domain\Enum\ProductType;
use App\Catalog\Domain\ValueObject\TelecomOfferId;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class TelecomOfferVersion
{
    private string $catalogSourceLabel;

    /**
     * @var list<TelecomOfferLineCommercialDetail>
     */
    private array $lineCommercialDetails;

    /**
     * @param list<TelecomOfferLineCommercialDetail> $lineCommercialDetails
     */
    private function __construct(
        private TelecomOfferVersionId $id,
        private TelecomOfferId $offerId,
        private int $versionNumber,
        string $catalogSourceLabel,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $validFrom,
        private ?DateTimeImmutable $validUntil,
        private TelecomOfferCommercialData $commercialData,
        private TelecomOfferNormalizedData $normalizedData,
        array $lineCommercialDetails,
    ) {
        if ($versionNumber < 1) {
            throw new InvalidArgumentException('Version number must be >= 1.');
        }

        $trimmedLabel = trim($catalogSourceLabel);

        if ($trimmedLabel === '') {
            throw new InvalidArgumentException('Catalog source label cannot be empty.');
        }

        $this->catalogSourceLabel = $trimmedLabel;

        if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
            throw new InvalidArgumentException('Valid until must be >= valid from.');
        }

        $this->lineCommercialDetails = $this->validateLineCommercialDetails($lineCommercialDetails);
    }

    /**
     * @param list<TelecomOfferLineCommercialDetail> $lineCommercialDetails
     */
    public static function createForOffer(
        TelecomOfferVersionId $id,
        TelecomOffer $offer,
        int $versionNumber,
        string $catalogSourceLabel,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validUntil,
        TelecomOfferCommercialData $commercialData,
        TelecomOfferNormalizedData $normalizedData,
        array $lineCommercialDetails,
    ): self {
        $instance = new self(
            $id,
            $offer->id(),
            $versionNumber,
            $catalogSourceLabel,
            $createdAt,
            $validFrom,
            $validUntil,
            $commercialData,
            $normalizedData,
            $lineCommercialDetails,
        );

        $instance->validateProductTypeCoherence($offer->productType());
        $instance->validateCommercialNormalizedCoherence();

        return $instance;
    }

    public function id(): TelecomOfferVersionId
    {
        return $this->id;
    }

    public function offerId(): TelecomOfferId
    {
        return $this->offerId;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function catalogSourceLabel(): string
    {
        return $this->catalogSourceLabel;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function validFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function validUntil(): ?DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function commercialData(): TelecomOfferCommercialData
    {
        return $this->commercialData;
    }

    public function normalizedData(): TelecomOfferNormalizedData
    {
        return $this->normalizedData;
    }

    /**
     * @return list<TelecomOfferLineCommercialDetail>
     */
    public function lineCommercialDetails(): array
    {
        return $this->lineCommercialDetails;
    }

    /**
     * @param list<TelecomOfferLineCommercialDetail> $list
     * @return list<TelecomOfferLineCommercialDetail>
     */
    private function validateLineCommercialDetails(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof TelecomOfferLineCommercialDetail) {
                throw new InvalidArgumentException('Each line detail must be a TelecomOfferLineCommercialDetail.');
            }

            $key = $item->lineOrdinal();

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate line ordinal: ' . $key);
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    private function validateProductTypeCoherence(ProductType $productType): void
    {
        $normalized = $this->normalizedData;
        $commercial = $this->commercialData;

        if ($productType === ProductType::MOBILE) {
            if ($normalized->fiberIncluded() !== false) {
                throw new InvalidArgumentException('MOBILE offer must have fiberIncluded = false.');
            }

            if ($normalized->fiberCapacityBand() !== null) {
                throw new InvalidArgumentException('MOBILE offer must have null fiberCapacityBand.');
            }

            if ($normalized->mobileLinesIncluded() <= 0) {
                throw new InvalidArgumentException('MOBILE offer must have mobileLinesIncluded > 0.');
            }

            if ($commercial->mobileLinesIncluded() <= 0) {
                throw new InvalidArgumentException('MOBILE offer commercial data must have mobileLinesIncluded > 0.');
            }

            if ($commercial->fiberSpeedMbps() !== null) {
                throw new InvalidArgumentException('MOBILE offer commercial data must have null fiberSpeedMbps.');
            }
        }

        if ($productType === ProductType::FIBER) {
            if ($normalized->fiberIncluded() !== true) {
                throw new InvalidArgumentException('FIBER offer must have fiberIncluded = true.');
            }

            if ($normalized->mobileLinesIncluded() !== 0) {
                throw new InvalidArgumentException('FIBER offer must have mobileLinesIncluded = 0.');
            }

            if ($normalized->mobileUsageBandSupported() !== null) {
                throw new InvalidArgumentException('FIBER offer must have null mobileUsageBandSupported.');
            }

            if ($commercial->mobileLinesIncluded() !== 0) {
                throw new InvalidArgumentException('FIBER offer commercial data must have mobileLinesIncluded = 0.');
            }

            if ($commercial->mobileDataDisplay() !== null) {
                throw new InvalidArgumentException('FIBER offer commercial data must have null mobileDataDisplay.');
            }
        }

        if ($productType === ProductType::FIBER_MOBILE) {
            if ($normalized->fiberIncluded() !== true) {
                throw new InvalidArgumentException('FIBER_MOBILE offer must have fiberIncluded = true.');
            }

            if ($normalized->mobileLinesIncluded() <= 0) {
                throw new InvalidArgumentException('FIBER_MOBILE offer must have mobileLinesIncluded > 0.');
            }

            if ($commercial->mobileLinesIncluded() <= 0) {
                throw new InvalidArgumentException('FIBER_MOBILE offer commercial data must have mobileLinesIncluded > 0.');
            }
        }
    }

    private function validateCommercialNormalizedCoherence(): void
    {
        $commercial = $this->commercialData;
        $normalized = $this->normalizedData;

        if ($commercial->mobileLinesIncluded() !== $normalized->mobileLinesIncluded()) {
            throw new InvalidArgumentException('Commercial and normalized mobile lines included must match.');
        }

        if ($commercial->tvIncluded() !== $normalized->tvIncluded()) {
            throw new InvalidArgumentException('Commercial and normalized TV included must match.');
        }

        if ($normalized->asymmetricLines() === true) {
            $details = $this->lineCommercialDetails;

            if (count($details) === 0) {
                throw new InvalidArgumentException('Asymmetric lines requires line commercial details.');
            }

            $expectedCount = $normalized->mobileLinesIncluded();

            if (count($details) !== $expectedCount) {
                throw new InvalidArgumentException('Line commercial details count must match mobile lines included for asymmetric lines.');
            }

            for ($i = 1; $i <= $expectedCount; $i++) {
                $found = false;

                foreach ($details as $detail) {
                    if ($detail->lineOrdinal() === $i) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    throw new InvalidArgumentException('Line commercial details must cover ordinals 1 to ' . $expectedCount . ' without gaps.');
                }
            }
        }
    }
}
