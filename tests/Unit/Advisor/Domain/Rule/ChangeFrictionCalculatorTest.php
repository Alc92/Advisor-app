<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\CompletenessLevel;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\UncertaintyFlag;
use App\Advisor\Domain\Rule\ChangeFrictionCalculator;
use App\Advisor\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class ChangeFrictionCalculatorTest extends TestCase
{
    public function test_active_commitment_returns_high_friction(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::YES),
            $this->buildInputQuality([]),
        );

        self::assertSame(ChangeFriction::HIGH, $result);
    }

    public function test_unknown_commitment_returns_medium_friction(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::UNKNOWN),
            $this->buildInputQuality([]),
        );

        self::assertSame(ChangeFriction::MEDIUM, $result);
    }

    public function test_no_commitment_without_relevant_uncertainty_returns_low_friction(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::NO),
            $this->buildInputQuality([]),
        );

        self::assertSame(ChangeFriction::LOW, $result);
    }

    public function test_unknown_promotion_uncertainty_raises_friction_to_medium(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::NO),
            $this->buildInputQuality([UncertaintyFlag::UNKNOWN_PROMOTION]),
        );

        self::assertSame(ChangeFriction::MEDIUM, $result);
    }

    public function test_multi_residence_uncertainty_raises_friction_to_medium(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::NO),
            $this->buildInputQuality([UncertaintyFlag::MULTI_RESIDENCE]),
        );

        self::assertSame(ChangeFriction::MEDIUM, $result);
    }

    public function test_unknown_tv_alone_does_not_raise_friction(): void
    {
        $calculator = new ChangeFrictionCalculator();

        $result = $calculator->calculate(
            $this->buildCurrentSituation(CommitmentStatus::NO),
            $this->buildInputQuality([UncertaintyFlag::UNKNOWN_TV]),
        );

        self::assertSame(ChangeFriction::LOW, $result);
    }

    private function buildCurrentSituation(CommitmentStatus $commitmentStatus): CurrentSituation
    {
        return new CurrentSituation(
            'Test Provider',
            ProductType::MOBILE,
            new Money('20.00', 'EUR'),
            1,
            MobileUsageBand::LOW,
            null,
            $commitmentStatus,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            false,
            false,
            DataProvenance::DECLARED_BY_USER,
        );
    }

    /**
     * @param list<UncertaintyFlag> $uncertaintyFlags
     */
    private function buildInputQuality(array $uncertaintyFlags): InputQuality
    {
        return new InputQuality(CompletenessLevel::MINIMUM_MET, $uncertaintyFlags);
    }
}
