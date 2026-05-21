<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use InvalidArgumentException;

final readonly class TelecomOfferLineCommercialDetail
{
    private string $mobileDataDisplay;

    public function __construct(
        private int $lineOrdinal,
        string $mobileDataDisplay,
    ) {
        if ($lineOrdinal < 1) {
            throw new InvalidArgumentException('Line ordinal must be >= 1.');
        }

        $trimmed = trim($mobileDataDisplay);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Mobile data display cannot be empty.');
        }

        $this->mobileDataDisplay = $trimmed;
    }

    public function lineOrdinal(): int
    {
        return $this->lineOrdinal;
    }

    public function mobileDataDisplay(): string
    {
        return $this->mobileDataDisplay;
    }
}
