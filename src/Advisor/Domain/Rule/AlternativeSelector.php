<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\HardFilteredOfferTrace;
use App\Advisor\Domain\Assessment\RankedOutOfferTrace;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\DiscardReasonCode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\ImpactType;
use InvalidArgumentException;

final class AlternativeSelector
{
    /**
     * @param list<AlternativeEvaluation> $alternatives
     */
    public function select(array $alternatives): AlternativeSelectionResult
    {
        $validatedAlternatives = [];
        $seenOfferIds = [];

        foreach ($alternatives as $alternative) {
            if (!$alternative instanceof AlternativeEvaluation) {
                throw new InvalidArgumentException('alternatives must be a list of AlternativeEvaluation.');
            }

            $offerId = $alternative->offerVersionId()->toString();
            if (isset($seenOfferIds[$offerId])) {
                throw new InvalidArgumentException('Duplicate offerVersionId in alternatives: ' . $offerId);
            }

            $seenOfferIds[$offerId] = true;
            $validatedAlternatives[] = $alternative;
        }

        if ($validatedAlternatives === []) {
            return new AlternativeSelectionResult(null, [], []);
        }

        $hardFilteredOffers = [];
        $rankedOutOffers = [];
        $candidates = [];

        foreach ($validatedAlternatives as $index => $alternative) {
            $hardReasons = [];

            if ($alternative->fitLevel() === FitLevel::LOW) {
                $hardReasons[] = DiscardReasonCode::INSUFFICIENT_FIT;
            }

            if ($alternative->changeFriction() === ChangeFriction::HIGH) {
                $hardReasons[] = DiscardReasonCode::FRICTION_TOO_HIGH;
            }

            if ($alternative->hasUnacceptableTradeOff()) {
                $hardReasons[] = DiscardReasonCode::UNACCEPTABLE_TRADEOFF;
            }

            if ($hardReasons !== []) {
                $hardFilteredOffers[] = new HardFilteredOfferTrace(
                    $alternative->offerVersionId()->value(),
                    array_values(array_unique($hardReasons)),
                );

                continue;
            }

            $savings = $alternative->estimatedImpact()->monthlySavingsEstimate();
            $savingsCents = $savings !== null ? $this->amountToCents($savings->amount()) : 0;
            $hasSufficientImprovement = $alternative->estimatedImpact()->impactType() === ImpactType::MONTHLY_SAVINGS
                && $savings !== null
                && $savingsCents >= 500;

            if (!$hasSufficientImprovement) {
                $rankedOutOffers[] = new RankedOutOfferTrace(
                    $alternative->offerVersionId()->value(),
                    [DiscardReasonCode::INSUFFICIENT_IMPROVEMENT],
                );

                continue;
            }

            $candidates[] = [
                'index' => $index,
                'alternative' => $alternative,
                'savingsCents' => $savingsCents,
            ];
        }

        if ($candidates === []) {
            return new AlternativeSelectionResult(
                null,
                $hardFilteredOffers,
                $rankedOutOffers,
            );
        }

        usort($candidates, function (array $a, array $b): int {
            /** @var AlternativeEvaluation $alternativeA */
            $alternativeA = $a['alternative'];
            /** @var AlternativeEvaluation $alternativeB */
            $alternativeB = $b['alternative'];

            $fitRankA = $this->fitRank($alternativeA->fitLevel());
            $fitRankB = $this->fitRank($alternativeB->fitLevel());
            if ($fitRankA !== $fitRankB) {
                return $fitRankB <=> $fitRankA;
            }

            if ($a['savingsCents'] !== $b['savingsCents']) {
                return $b['savingsCents'] <=> $a['savingsCents'];
            }

            $frictionRankA = $this->frictionRank($alternativeA->changeFriction());
            $frictionRankB = $this->frictionRank($alternativeB->changeFriction());
            if ($frictionRankA !== $frictionRankB) {
                return $frictionRankA <=> $frictionRankB;
            }

            return $a['index'] <=> $b['index'];
        });

        /** @var AlternativeEvaluation $selectedAlternative */
        $selectedAlternative = $candidates[0]['alternative'];

        return new AlternativeSelectionResult(
            $selectedAlternative,
            $hardFilteredOffers,
            $rankedOutOffers,
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

    private function fitRank(FitLevel $fitLevel): int
    {
        return match ($fitLevel) {
            FitLevel::HIGH => 3,
            FitLevel::MEDIUM => 2,
            FitLevel::LOW => 1,
        };
    }

    private function frictionRank(ChangeFriction $changeFriction): int
    {
        return match ($changeFriction) {
            ChangeFriction::LOW => 1,
            ChangeFriction::MEDIUM => 2,
            ChangeFriction::HIGH => 3,
        };
    }
}
