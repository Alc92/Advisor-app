<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\FitLevel;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;

final readonly class AlternativeEvaluation
{
    public function __construct(
        private TelecomOfferVersionId $offerVersionId,
        private FitLevel $fitLevel,
        private EstimatedImpact $estimatedImpact,
        private ChangeFriction $changeFriction,
        private bool $hasUnacceptableTradeOff,
    ) {
    }

    public function offerVersionId(): TelecomOfferVersionId
    {
        return $this->offerVersionId;
    }

    public function fitLevel(): FitLevel
    {
        return $this->fitLevel;
    }

    public function estimatedImpact(): EstimatedImpact
    {
        return $this->estimatedImpact;
    }

    public function changeFriction(): ChangeFriction
    {
        return $this->changeFriction;
    }

    public function hasUnacceptableTradeOff(): bool
    {
        return $this->hasUnacceptableTradeOff;
    }
}
