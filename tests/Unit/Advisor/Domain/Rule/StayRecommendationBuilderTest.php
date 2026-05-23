<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Rule\StayRecommendationBuilder;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StayRecommendationBuilderTest extends TestCase
{
    public function test_insufficient_improvement_builds_stay_recommendation(): void
    {
        $builder = new StayRecommendationBuilder();
        $estimatedImpact = new EstimatedImpact(
            new Money('3', 'EUR'),
            new Percentage('5'),
            ImpactType::NO_CLEAR_IMPACT,
            'Ahorro mensual estimado insuficiente.',
        );

        $recommendation = $builder->buildForInsufficientImprovement(
            $estimatedImpact,
            'No merece la pena cambiar.',
            ['Perder cobertura móvil'],
            ['Posible subida de precios'],
        );

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::NO_CLEAR_IMPROVEMENT, $recommendation->reasonCode());
        self::assertNull($recommendation->waitKind());
        self::assertNull($recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame($estimatedImpact, $recommendation->estimatedImpact());
    }

    public function test_trade_off_not_worth_it_builds_stay_recommendation(): void
    {
        $builder = new StayRecommendationBuilder();
        $estimatedImpact = new EstimatedImpact(
            null,
            null,
            ImpactType::NO_CLEAR_IMPACT,
            'Impacto no claro.',
        );

        $recommendation = $builder->buildForTradeOffNotWorthIt(
            $estimatedImpact,
            'Los trade-offs no compensan.',
            ['Perder velocidad de fibra', 'Menos cobertura móvil'],
        );

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::TRADEOFF_NOT_WORTH_IT, $recommendation->reasonCode());
        self::assertSame(['Perder velocidad de fibra', 'Menos cobertura móvil'], $recommendation->tradeOffs());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
    }

    public function test_already_optimized_builds_stay_recommendation(): void
    {
        $builder = new StayRecommendationBuilder();

        $recommendation = $builder->buildForAlreadyOptimized(
            'Ya tienes la mejor oferta disponible.',
            ['Posible subida de precios general'],
        );

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::ALREADY_OPTIMIZED, $recommendation->reasonCode());
        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $recommendation->estimatedImpact()->impactType());
        self::assertNull($recommendation->estimatedImpact()->monthlySavingsEstimate());
        self::assertNull($recommendation->estimatedImpact()->relativeSavingsEstimate());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
    }

    public function test_stay_recommendation_never_includes_suggested_offer_or_snapshot(): void
    {
        $builder = new StayRecommendationBuilder();

        $insufficientImprovement = $builder->buildForInsufficientImprovement(
            new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Ahorro insuficiente.'),
            'No compensa.',
        );
        $tradeOffNotWorthIt = $builder->buildForTradeOffNotWorthIt(
            new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Impacto no claro.'),
            'No compensa.',
            ['Perder velocidad'],
        );
        $alreadyOptimized = $builder->buildForAlreadyOptimized(
            'Ya optimizado.',
        );

        foreach ([$insufficientImprovement, $tradeOffNotWorthIt, $alreadyOptimized] as $recommendation) {
            self::assertSame(Decision::STAY, $recommendation->decision());
            self::assertNull($recommendation->suggestedOfferVersionId());
            self::assertNull($recommendation->suggestedOfferSnapshot());
        }
    }

    public function test_sufficient_monthly_savings_cannot_build_insufficient_improvement_stay(): void
    {
        $builder = new StayRecommendationBuilder();
        $estimatedImpact = new EstimatedImpact(
            new Money('5', 'EUR'),
            null,
            ImpactType::MONTHLY_SAVINGS,
            'Ahorro mensual de 5 EUR.',
        );

        $this->expectException(InvalidArgumentException::class);

        $builder->buildForInsufficientImprovement(
            $estimatedImpact,
            'No debería llegar aquí.',
        );
    }

    public function test_non_string_list_values_are_rejected(): void
    {
        $builder = new StayRecommendationBuilder();

        $this->expectException(InvalidArgumentException::class);

        $builder->buildForInsufficientImprovement(
            new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Ahorro insuficiente.'),
            'No compensa.',
            ['válido', 123],
        );
    }
}
