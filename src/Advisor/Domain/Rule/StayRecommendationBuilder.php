<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use InvalidArgumentException;

final class StayRecommendationBuilder
{
    /**
     * @param list<string> $tradeOffs
     * @param list<string> $risks
     */
    public function buildForInsufficientImprovement(
        EstimatedImpact $estimatedImpact,
        string $mainExplanation,
        array $tradeOffs = [],
        array $risks = [],
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        if ($estimatedImpact->impactType() !== ImpactType::NO_CLEAR_IMPACT) {
            throw new InvalidArgumentException('Insufficient improvement requires NO_CLEAR_IMPACT impact type.');
        }

        $monthlySavings = $estimatedImpact->monthlySavingsEstimate();

        if ($monthlySavings !== null && $this->amountToCents($monthlySavings->amount()) >= 500) {
            throw new InvalidArgumentException('Monthly savings >= 5 EUR is not insufficient improvement.');
        }

        return new Recommendation(
            decision: Decision::STAY,
            reasonCode: DecisionReasonCode::NO_CLEAR_IMPROVEMENT,
            waitKind: null,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $estimatedImpact,
            mainExplanation: $mainExplanation,
            tradeOffs: $tradeOffs,
            risks: $risks,
            uncertaintySummary: null,
            analysisLimitations: [],
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }

    /**
     * @param list<string> $tradeOffs
     * @param list<string> $risks
     */
    public function buildForTradeOffNotWorthIt(
        EstimatedImpact $estimatedImpact,
        string $mainExplanation,
        array $tradeOffs,
        array $risks = [],
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        if (count($tradeOffs) === 0) {
            throw new InvalidArgumentException('Trade-offs cannot be empty.');
        }

        return new Recommendation(
            decision: Decision::STAY,
            reasonCode: DecisionReasonCode::TRADEOFF_NOT_WORTH_IT,
            waitKind: null,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $estimatedImpact,
            mainExplanation: $mainExplanation,
            tradeOffs: $tradeOffs,
            risks: $risks,
            uncertaintySummary: null,
            analysisLimitations: [],
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }

    /**
     * @param list<string> $risks
     */
    public function buildForAlreadyOptimized(
        string $mainExplanation,
        array $risks = [],
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        $estimatedImpact = new EstimatedImpact(
            monthlySavingsEstimate: null,
            relativeSavingsEstimate: null,
            impactType: ImpactType::NO_CLEAR_IMPACT,
            summary: 'No se estima mejora clara porque la situación actual ya está optimizada.',
        );

        return new Recommendation(
            decision: Decision::STAY,
            reasonCode: DecisionReasonCode::ALREADY_OPTIMIZED,
            waitKind: null,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $estimatedImpact,
            mainExplanation: $mainExplanation,
            tradeOffs: [],
            risks: $risks,
            uncertaintySummary: null,
            analysisLimitations: [],
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }

    private function amountToCents(string $amount): int
    {
        $normalized = trim($amount);
        $parts = explode('.', $normalized, 2);
        $units = (int) $parts[0];
        $decimals = isset($parts[1]) ? (int) str_pad(substr($parts[1], 0, 2), 2, '0') : 0;

        return ($units * 100) + $decimals;
    }
}
