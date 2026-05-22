<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;

final class EstimatedImpactCalculator
{
    public function calculate(
        Money $currentMonthlyPrice,
        Money $offerMonthlyPrice,
    ): EstimatedImpact {
        $currentCents = $this->amountToCents($currentMonthlyPrice->amount());
        $offerCents = $this->amountToCents($offerMonthlyPrice->amount());
        $savingsCents = $currentCents - $offerCents;

        if ($savingsCents <= 0) {
            return new EstimatedImpact(
                null,
                null,
                ImpactType::NO_CLEAR_IMPACT,
                'No hay ahorro mensual estimado.',
            );
        }

        $monthlySavingsEstimate = new Money(
            $this->centsToAmount($savingsCents),
            $currentMonthlyPrice->currency(),
        );

        $relativeSavingsEstimate = new Percentage(
            (string) round(($savingsCents * 100) / $currentCents, 2),
        );

        if ($savingsCents >= 1000) {
            return new EstimatedImpact(
                $monthlySavingsEstimate,
                $relativeSavingsEstimate,
                ImpactType::MONTHLY_SAVINGS,
                'Ahorro mensual estimado claro.',
            );
        }

        if ($savingsCents >= 500) {
            return new EstimatedImpact(
                $monthlySavingsEstimate,
                $relativeSavingsEstimate,
                ImpactType::MONTHLY_SAVINGS,
                'Ahorro mensual estimado condicionado.',
            );
        }

        return new EstimatedImpact(
            $monthlySavingsEstimate,
            $relativeSavingsEstimate,
            ImpactType::NO_CLEAR_IMPACT,
            'Ahorro mensual estimado insuficiente.',
        );
    }

    private function amountToCents(string $amount): int
    {
        $normalized = trim($amount);

        if (str_contains($normalized, '.')) {
            [$units, $decimals] = explode('.', $normalized, 2);
        } else {
            $units = $normalized;
            $decimals = '0';
        }

        $decimals = str_pad(substr($decimals, 0, 2), 2, '0');

        return ((int) $units * 100) + (int) $decimals;
    }

    private function centsToAmount(int $cents): string
    {
        $units = intdiv($cents, 100);
        $decimals = $cents % 100;

        if ($decimals === 0) {
            return (string) $units;
        }

        return sprintf('%d.%02d', $units, $decimals);
    }
}
