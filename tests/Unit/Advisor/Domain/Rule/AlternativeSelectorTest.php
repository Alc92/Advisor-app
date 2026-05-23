<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\DiscardReasonCode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Rule\AlternativeEvaluation;
use App\Advisor\Domain\Rule\AlternativeSelector;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AlternativeSelectorTest extends TestCase
{
    public function test_low_fit_alternative_is_hard_filtered(): void
    {
        $selector = new AlternativeSelector();

        $alternative = $this->buildAlternative(
            FitLevel::LOW,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $result = $selector->select([$alternative]);

        self::assertNull($result->selectedAlternative());
        self::assertCount(1, $result->hardFilteredOffers());
        self::assertSame([DiscardReasonCode::INSUFFICIENT_FIT], $result->hardFilteredOffers()[0]->reasonCodes());
        self::assertSame([], $result->rankedOutOffers());
    }

    public function test_higher_fit_wins_over_slightly_higher_savings(): void
    {
        $selector = new AlternativeSelector();

        $alternativeA = $this->buildAlternative(
            FitLevel::HIGH,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );
        $alternativeB = $this->buildAlternative(
            FitLevel::MEDIUM,
            15,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $result = $selector->select([$alternativeA, $alternativeB]);

        self::assertSame($alternativeA->offerVersionId()->toString(), $result->selectedAlternative()?->offerVersionId()->toString());
        self::assertCount(0, $result->rankedOutOffers());
    }

    public function test_insufficient_savings_is_ranked_out_and_not_selected(): void
    {
        $selector = new AlternativeSelector();

        $alternative = $this->buildAlternative(
            FitLevel::HIGH,
            3,
            ImpactType::NO_CLEAR_IMPACT,
            ChangeFriction::LOW,
            false,
        );

        $result = $selector->select([$alternative]);

        self::assertNull($result->selectedAlternative());
        self::assertSame([], $result->hardFilteredOffers());
        self::assertCount(1, $result->rankedOutOffers());
        self::assertSame([DiscardReasonCode::INSUFFICIENT_IMPROVEMENT], $result->rankedOutOffers()[0]->reasonCodes());
    }

    public function test_high_friction_alternative_is_hard_filtered(): void
    {
        $selector = new AlternativeSelector();

        $alternative = $this->buildAlternative(
            FitLevel::HIGH,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::HIGH,
            false,
        );

        $result = $selector->select([$alternative]);

        self::assertNull($result->selectedAlternative());
        self::assertCount(1, $result->hardFilteredOffers());
        self::assertContains(DiscardReasonCode::FRICTION_TOO_HIGH, $result->hardFilteredOffers()[0]->reasonCodes());
    }

    public function test_unacceptable_trade_off_is_hard_filtered(): void
    {
        $selector = new AlternativeSelector();

        $alternative = $this->buildAlternative(
            FitLevel::HIGH,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            true,
        );

        $result = $selector->select([$alternative]);

        self::assertNull($result->selectedAlternative());
        self::assertCount(1, $result->hardFilteredOffers());
        self::assertContains(DiscardReasonCode::UNACCEPTABLE_TRADEOFF, $result->hardFilteredOffers()[0]->reasonCodes());
    }

    public function test_lower_friction_wins_when_fit_and_savings_are_equal(): void
    {
        $selector = new AlternativeSelector();

        $alternativeA = $this->buildAlternative(
            FitLevel::HIGH,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::MEDIUM,
            false,
        );
        $alternativeB = $this->buildAlternative(
            FitLevel::HIGH,
            10,
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $result = $selector->select([$alternativeA, $alternativeB]);

        self::assertSame($alternativeB->offerVersionId()->toString(), $result->selectedAlternative()?->offerVersionId()->toString());
        self::assertCount(0, $result->rankedOutOffers());
    }

    private function buildAlternative(
        FitLevel $fitLevel,
        int $monthlySavings,
        ImpactType $impactType,
        ChangeFriction $changeFriction,
        bool $hasUnacceptableTradeOff,
    ): AlternativeEvaluation {
        $money = new Money((string) $monthlySavings, 'EUR');

        return new AlternativeEvaluation(
            TelecomOfferVersionId::fromUuid(Uuid::v4()),
            $fitLevel,
            new EstimatedImpact(
                $money,
                new Percentage('10'),
                $impactType,
                'impact summary',
            ),
            $changeFriction,
            $hasUnacceptableTradeOff,
        );
    }
}
