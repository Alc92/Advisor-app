<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\ValueObject\Money;
use InvalidArgumentException;

final readonly class RecommendedOfferSnapshot
{
    private string $provider;
    private string $commercialName;
    private ?string $mobileDataDisplay;

    public function __construct(
        string $provider,
        string $commercialName,
        private Money $monthlyPrice,
        private int $mobileLinesIncluded,
        private bool $tvIncluded,
        private ?int $fiberSpeedMbps,
        ?string $mobileDataDisplay,
    ) {
        $trimmedProvider = trim($provider);

        if ($trimmedProvider === '') {
            throw new InvalidArgumentException('Provider cannot be empty.');
        }

        $trimmedName = trim($commercialName);

        if ($trimmedName === '') {
            throw new InvalidArgumentException('Commercial name cannot be empty.');
        }

        if ($mobileLinesIncluded < 0) {
            throw new InvalidArgumentException('Mobile lines included must be >= 0.');
        }

        if ($fiberSpeedMbps !== null && $fiberSpeedMbps <= 0) {
            throw new InvalidArgumentException('Fiber speed must be > 0.');
        }

        if ($mobileDataDisplay !== null && trim($mobileDataDisplay) === '') {
            throw new InvalidArgumentException('Mobile data display cannot be empty.');
        }

        $this->provider = $trimmedProvider;
        $this->commercialName = $trimmedName;
        $this->mobileDataDisplay = $mobileDataDisplay !== null ? trim($mobileDataDisplay) : null;
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
}
