<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\AdditionalConditionProfile;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\TriState;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\Rule\FitCalculator;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use App\Catalog\Domain\TelecomOfferNormalizedData;
use PHPUnit\Framework\TestCase;

final class FitCalculatorTest extends TestCase
{
    private function buildCurrentSituation(
        ProductType $productType,
        ?MobileUsageBand $mobileUsageBand = null,
        ?FiberNeedBand $fiberNeedBand = null,
        ?bool $tvIncluded = false,
    ): CurrentSituation {
        return new CurrentSituation(
            'Test Provider',
            $productType,
            new Money('20.00', 'EUR'),
            $productType === ProductType::FIBER ? null : 1,
            $mobileUsageBand,
            $fiberNeedBand,
            CommitmentStatus::NO,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            $tvIncluded,
            false,
            DataProvenance::DECLARED_BY_USER,
        );
    }

    private function buildOfferNormalizedData(
        ?FiberCapacityBand $fiberCapacityBand = null,
        ?MobileUsageBand $mobileUsageBandSupported = null,
        int $mobileLinesIncluded = 0,
        bool $fiberIncluded = false,
        bool $tvIncluded = false,
        bool $asymmetricLines = false,
    ): TelecomOfferNormalizedData {
        return new TelecomOfferNormalizedData(
            $fiberCapacityBand,
            $mobileUsageBandSupported,
            $mobileLinesIncluded,
            $fiberIncluded,
            $tvIncluded,
            $asymmetricLines,
        );
    }

    public function test_medium_mobile_usage_with_medium_or_higher_offer_returns_high(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::MOBILE,
            MobileUsageBand::MEDIUM,
        );

        $offer = $this->buildOfferNormalizedData(
            mobileUsageBandSupported: MobileUsageBand::MEDIUM,
            mobileLinesIncluded: 1,
            tvIncluded: true,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::BALANCE, null, $offer);

        self::assertSame(FitLevel::HIGH, $result);
    }

    public function test_high_fiber_need_with_basic_offer_returns_low(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::FIBER,
            fiberNeedBand: FiberNeedBand::HIGH,
        );

        $offer = $this->buildOfferNormalizedData(
            fiberCapacityBand: FiberCapacityBand::BASIC,
            fiberIncluded: true,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::BALANCE, null, $offer);

        self::assertSame(FitLevel::LOW, $result);
    }

    public function test_asymmetric_multi_line_offer_is_capped_at_medium(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::MOBILE,
            MobileUsageBand::MEDIUM,
        );

        $offer = $this->buildOfferNormalizedData(
            mobileUsageBandSupported: MobileUsageBand::HIGH,
            mobileLinesIncluded: 2,
            asymmetricLines: true,
            tvIncluded: true,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::BALANCE, null, $offer);

        self::assertSame(FitLevel::MEDIUM, $result);
    }

    public function test_important_tv_missing_is_capped_at_medium_for_balance_preference(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::MOBILE,
            MobileUsageBand::MEDIUM,
        );

        $additionalProfile = new AdditionalConditionProfile(
            null,
            null,
            null,
            TriState::YES,
        );

        $offer = $this->buildOfferNormalizedData(
            mobileUsageBandSupported: MobileUsageBand::MEDIUM,
            mobileLinesIncluded: 1,
            tvIncluded: false,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::BALANCE, $additionalProfile, $offer);

        self::assertSame(FitLevel::MEDIUM, $result);
    }

    public function test_important_tv_missing_is_low_for_keep_conditions_preference(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::MOBILE,
            MobileUsageBand::MEDIUM,
        );

        $additionalProfile = new AdditionalConditionProfile(
            null,
            null,
            null,
            TriState::YES,
        );

        $offer = $this->buildOfferNormalizedData(
            mobileUsageBandSupported: MobileUsageBand::MEDIUM,
            mobileLinesIncluded: 1,
            tvIncluded: false,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::KEEP_CONDITIONS, $additionalProfile, $offer);

        self::assertSame(FitLevel::LOW, $result);
    }

    public function test_fiber_mobile_with_both_components_covered_returns_high(): void
    {
        $currentSituation = $this->buildCurrentSituation(
            ProductType::FIBER_MOBILE,
            MobileUsageBand::MEDIUM,
            FiberNeedBand::STANDARD,
        );

        $offer = $this->buildOfferNormalizedData(
            fiberCapacityBand: FiberCapacityBand::HIGH,
            mobileUsageBandSupported: MobileUsageBand::HIGH,
            mobileLinesIncluded: 1,
            fiberIncluded: true,
            tvIncluded: true,
        );

        $calculator = new FitCalculator();
        $result = $calculator->calculate($currentSituation, UserPreference::BALANCE, null, $offer);

        self::assertSame(FitLevel::HIGH, $result);
    }
}
