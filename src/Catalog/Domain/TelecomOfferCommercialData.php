<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Advisor\Domain\ValueObject\Money;
use InvalidArgumentException;

final readonly class TelecomOfferCommercialData
{
    private ?string $mobileDataDisplay;
    private ?string $notes;

    public function __construct(
        private ?int $fiberSpeedMbps,
        ?string $mobileDataDisplay,
        private Money $monthlyPrice,
        private int $mobileLinesIncluded,
        private bool $tvIncluded,
        ?string $notes,
    ) {
        if ($fiberSpeedMbps !== null && $fiberSpeedMbps <= 0) {
            throw new InvalidArgumentException('Fiber speed must be > 0.');
        }

        if ($mobileLinesIncluded < 0) {
            throw new InvalidArgumentException('Mobile lines included must be >= 0.');
        }

        if ($mobileDataDisplay !== null && trim($mobileDataDisplay) === '') {
            throw new InvalidArgumentException('Mobile data display cannot be empty.');
        }

        if ($notes !== null && trim($notes) === '') {
            throw new InvalidArgumentException('Notes cannot be empty.');
        }

        $this->mobileDataDisplay = $mobileDataDisplay !== null ? trim($mobileDataDisplay) : null;
        $this->notes = $notes !== null ? trim($notes) : null;
    }

    public function fiberSpeedMbps(): ?int
    {
        return $this->fiberSpeedMbps;
    }

    public function mobileDataDisplay(): ?string
    {
        return $this->mobileDataDisplay;
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

    public function notes(): ?string
    {
        return $this->notes;
    }
}
