<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WaitRecommendationBuilderTest extends TestCase
{
    public function test_active_commitment_builds_actionable_wait_recommendation(): void
    {
        $builder = new WaitRecommendationBuilder();
        $commitmentEndDate = new DateTimeImmutable('2026-06-15');

        $recommendation = $builder->buildForActiveCommitment(
            $commitmentEndDate,
            'Esperar al fin de la permanencia.',
            ['Posible subida de precios'],
        );

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_FOR_COMMITMENT_END, $recommendation->reasonCode());
        self::assertSame(WaitKind::TIMING, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::COMMITMENT_END, $recommendation->reviewTrigger());
        self::assertSame(2026, $recommendation->recommendedReviewMoment()?->year());
        self::assertSame(6, $recommendation->recommendedReviewMoment()?->month());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $recommendation->estimatedImpact()->impactType());
        self::assertNull($recommendation->estimatedImpact()->monthlySavingsEstimate());
        self::assertNull($recommendation->estimatedImpact()->relativeSavingsEstimate());
    }

    public function test_relevant_uncertainty_builds_wait_recommendation(): void
    {
        $builder = new WaitRecommendationBuilder();
        $uncertaintySummary = 'No se sabe si el usuario necesita fibra.';
        $limitations = [AnalysisLimitationCode::MISSING_CRITICAL_DATA];

        $recommendation = $builder->buildForRelevantUncertainty(
            $uncertaintySummary,
            'Falta información clave.',
            $limitations,
        );

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
        self::assertSame($uncertaintySummary, $recommendation->uncertaintySummary());
        self::assertSame($limitations, $recommendation->analysisLimitations());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $recommendation->estimatedImpact()->impactType());
        self::assertNull($recommendation->estimatedImpact()->monthlySavingsEstimate());
        self::assertNull($recommendation->estimatedImpact()->relativeSavingsEstimate());
    }

    public function test_minimum_input_not_met_builds_wait_recommendation(): void
    {
        $builder = new WaitRecommendationBuilder();
        $limitations = [AnalysisLimitationCode::MISSING_CRITICAL_DATA];

        $recommendation = $builder->buildForMinimumInputNotMet(
            'Faltan datos mínimos para evaluar.',
            $limitations,
        );

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
        self::assertSame('Falta información mínima para evaluar alternativas.', $recommendation->uncertaintySummary());
        self::assertSame($limitations, $recommendation->analysisLimitations());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $recommendation->estimatedImpact()->impactType());
        self::assertNull($recommendation->estimatedImpact()->monthlySavingsEstimate());
        self::assertNull($recommendation->estimatedImpact()->relativeSavingsEstimate());
    }

    public function test_wait_recommendations_always_include_wait_kind_and_review_trigger(): void
    {
        $builder = new WaitRecommendationBuilder();

        $activeCommitment = $builder->buildForActiveCommitment(
            new DateTimeImmutable('2026-06-15'),
            'Esperar al fin de la permanencia.',
        );
        $relevantUncertainty = $builder->buildForRelevantUncertainty(
            'Incertidumbre sobre necesidades.',
            'Falta información.',
            [AnalysisLimitationCode::MISSING_CRITICAL_DATA],
        );
        $minimumInputNotMet = $builder->buildForMinimumInputNotMet(
            'Faltan datos mínimos.',
            [AnalysisLimitationCode::MISSING_CRITICAL_DATA],
        );

        foreach ([$activeCommitment, $relevantUncertainty, $minimumInputNotMet] as $recommendation) {
            self::assertSame(Decision::WAIT, $recommendation->decision());
            self::assertNotNull($recommendation->waitKind());
            self::assertNotNull($recommendation->reviewTrigger());
            self::assertSame(ImpactType::NO_CLEAR_IMPACT, $recommendation->estimatedImpact()->impactType());
            self::assertNull($recommendation->estimatedImpact()->monthlySavingsEstimate());
            self::assertNull($recommendation->estimatedImpact()->relativeSavingsEstimate());
        }
    }

    public function test_insufficient_improvement_cannot_produce_wait(): void
    {
        $builder = new WaitRecommendationBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient improvement must not produce WAIT.');

        $builder->rejectInsufficientImprovement();
    }
}
