<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Rule\EstimatedImpactCalculator;
use App\Advisor\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class EstimatedImpactCalculatorTest extends TestCase
{
    public function test_fifteen_euro_monthly_saving_returns_clear_monthly_savings_impact(): void
    {
        $calculator = new EstimatedImpactCalculator();

        $result = $calculator->calculate(
            new Money('50', 'EUR'),
            new Money('35', 'EUR'),
        );

        self::assertSame(ImpactType::MONTHLY_SAVINGS, $result->impactType());
        self::assertNotNull($result->monthlySavingsEstimate());
        self::assertSame('15', $result->monthlySavingsEstimate()?->amount());
        self::assertNotNull($result->relativeSavingsEstimate());
        self::assertSame('Ahorro mensual estimado claro.', $result->summary());
    }

    public function test_seven_euro_monthly_saving_returns_conditioned_monthly_savings_impact(): void
    {
        $calculator = new EstimatedImpactCalculator();

        $result = $calculator->calculate(
            new Money('50', 'EUR'),
            new Money('43', 'EUR'),
        );

        self::assertSame(ImpactType::MONTHLY_SAVINGS, $result->impactType());
        self::assertNotNull($result->monthlySavingsEstimate());
        self::assertSame('7', $result->monthlySavingsEstimate()?->amount());
        self::assertNotNull($result->relativeSavingsEstimate());
        self::assertSame('Ahorro mensual estimado condicionado.', $result->summary());
    }

    public function test_three_euro_monthly_saving_returns_insufficient_impact(): void
    {
        $calculator = new EstimatedImpactCalculator();

        $result = $calculator->calculate(
            new Money('50', 'EUR'),
            new Money('47', 'EUR'),
        );

        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $result->impactType());
        self::assertNotNull($result->monthlySavingsEstimate());
        self::assertSame('3', $result->monthlySavingsEstimate()?->amount());
        self::assertNotNull($result->relativeSavingsEstimate());
        self::assertSame('Ahorro mensual estimado insuficiente.', $result->summary());
    }

    public function test_more_expensive_offer_does_not_invent_service_improvement(): void
    {
        $calculator = new EstimatedImpactCalculator();

        $result = $calculator->calculate(
            new Money('50', 'EUR'),
            new Money('55', 'EUR'),
        );

        self::assertSame(ImpactType::NO_CLEAR_IMPACT, $result->impactType());
        self::assertNull($result->monthlySavingsEstimate());
        self::assertNull($result->relativeSavingsEstimate());
        self::assertSame('No hay ahorro mensual estimado.', $result->summary());
    }
}
