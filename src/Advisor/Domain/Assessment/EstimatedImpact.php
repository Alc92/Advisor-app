<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;
use InvalidArgumentException;

final readonly class EstimatedImpact
{
    private string $summary;

    public function __construct(
        private ?Money $monthlySavingsEstimate,
        private ?Percentage $relativeSavingsEstimate,
        private ImpactType $impactType,
        string $summary,
    ) {
        $trimmed = trim($summary);

        if ($trimmed === '') {
            throw new InvalidArgumentException('EstimatedImpact summary cannot be empty.');
        }

        $this->summary = $trimmed;
    }

    public function monthlySavingsEstimate(): ?Money
    {
        return $this->monthlySavingsEstimate;
    }

    public function relativeSavingsEstimate(): ?Percentage
    {
        return $this->relativeSavingsEstimate;
    }

    public function impactType(): ImpactType
    {
        return $this->impactType;
    }

    public function summary(): string
    {
        return $this->summary;
    }
}
