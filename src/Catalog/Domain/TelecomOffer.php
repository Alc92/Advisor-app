<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Advisor\Domain\Enum\ProductType;
use App\Catalog\Domain\ValueObject\TelecomOfferId;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class TelecomOffer
{
    private string $provider;
    private string $commercialName;

    public function __construct(
        private TelecomOfferId $id,
        string $provider,
        private ProductType $productType,
        string $commercialName,
        private DateTimeImmutable $createdAt,
    ) {
        $trimmedProvider = trim($provider);

        if ($trimmedProvider === '') {
            throw new InvalidArgumentException('Provider cannot be empty.');
        }

        $trimmedCommercialName = trim($commercialName);

        if ($trimmedCommercialName === '') {
            throw new InvalidArgumentException('Commercial name cannot be empty.');
        }

        $this->provider = $trimmedProvider;
        $this->commercialName = $trimmedCommercialName;
    }

    public function id(): TelecomOfferId
    {
        return $this->id;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function productType(): ProductType
    {
        return $this->productType;
    }

    public function commercialName(): string
    {
        return $this->commercialName;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
