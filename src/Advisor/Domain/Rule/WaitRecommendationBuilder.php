<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use DateTimeImmutable;
use InvalidArgumentException;

final class WaitRecommendationBuilder
{
    /**
     * @param list<string> $risks
     */
    public function buildForActiveCommitment(
        DateTimeImmutable $commitmentEndDate,
        string $mainExplanation,
        array $risks = [],
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        return new Recommendation(
            decision: Decision::WAIT,
            reasonCode: DecisionReasonCode::WAIT_FOR_COMMITMENT_END,
            waitKind: WaitKind::TIMING,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $this->noClearEconomicImpact(),
            mainExplanation: $mainExplanation,
            tradeOffs: [],
            risks: $risks,
            uncertaintySummary: null,
            analysisLimitations: [],
            recommendedReviewMoment: new ApproximateDate(
                (int) $commitmentEndDate->format('Y'),
                (int) $commitmentEndDate->format('n'),
            ),
            reviewTrigger: ReviewTrigger::COMMITMENT_END,
        );
    }

    /**
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function buildForRelevantUncertainty(
        string $uncertaintySummary,
        string $mainExplanation,
        array $analysisLimitations,
        ?DateTimeImmutable $recommendedReviewMoment = null,
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        $trimmedUncertainty = trim($uncertaintySummary);

        if ($trimmedUncertainty === '') {
            throw new InvalidArgumentException('Uncertainty summary cannot be empty.');
        }

        if (count($analysisLimitations) === 0) {
            throw new InvalidArgumentException('Analysis limitations cannot be empty.');
        }

        return new Recommendation(
            decision: Decision::WAIT,
            reasonCode: DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
            waitKind: WaitKind::UNCERTAINTY_OR_MISSING_INFO,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $this->noClearEconomicImpact(),
            mainExplanation: $mainExplanation,
            tradeOffs: [],
            risks: [],
            uncertaintySummary: $trimmedUncertainty,
            analysisLimitations: $analysisLimitations,
            recommendedReviewMoment: $recommendedReviewMoment !== null
                ? new ApproximateDate(
                    (int) $recommendedReviewMoment->format('Y'),
                    (int) $recommendedReviewMoment->format('n'),
                )
                : null,
            reviewTrigger: ReviewTrigger::CHECK_MISSING_INFORMATION,
        );
    }

    /**
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function buildForMinimumInputNotMet(
        string $mainExplanation,
        array $analysisLimitations,
    ): Recommendation {
        if (trim($mainExplanation) === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        if (count($analysisLimitations) === 0) {
            throw new InvalidArgumentException('Analysis limitations cannot be empty.');
        }

        return new Recommendation(
            decision: Decision::WAIT,
            reasonCode: DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
            waitKind: WaitKind::UNCERTAINTY_OR_MISSING_INFO,
            suggestedOfferVersionId: null,
            suggestedOfferSnapshot: null,
            estimatedImpact: $this->noClearEconomicImpact(),
            mainExplanation: $mainExplanation,
            tradeOffs: [],
            risks: [],
            uncertaintySummary: 'Falta información mínima para evaluar alternativas.',
            analysisLimitations: $analysisLimitations,
            recommendedReviewMoment: null,
            reviewTrigger: ReviewTrigger::CHECK_MISSING_INFORMATION,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function rejectInsufficientImprovement(): never
    {
        throw new InvalidArgumentException('Insufficient improvement must not produce WAIT.');
    }

    private function noClearEconomicImpact(): EstimatedImpact
    {
        return new EstimatedImpact(
            impactType: ImpactType::NO_CLEAR_IMPACT,
            monthlySavingsEstimate: null,
            relativeSavingsEstimate: null,
            summary: 'No se estima impacto económico porque la recomendación es esperar.',
        );
    }
}
