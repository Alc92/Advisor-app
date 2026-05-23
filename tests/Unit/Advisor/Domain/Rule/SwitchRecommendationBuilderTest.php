<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Rule\AlternativeEvaluation;
use App\Advisor\Domain\Rule\SwitchRecommendationBuilder;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;
use App\Catalog\Domain\TelecomOffer;
use App\Catalog\Domain\TelecomOfferCommercialData;
use App\Catalog\Domain\TelecomOfferNormalizedData;
use App\Catalog\Domain\TelecomOfferVersion;
use App\Catalog\Domain\ValueObject\TelecomOfferId;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SwitchRecommendationBuilderTest extends TestCase
{
    public function test_valid_selected_alternative_builds_switch_recommendation_with_snapshot(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = $this->buildOffer($offerId, 'Provider A', 'Fiber Plan 300');
        $offerVersion = $this->buildFiberVersion($offer, 300, '49.99');
        $alternative = $this->buildAlternative(
            $offerVersion->id(),
            FitLevel::HIGH,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $recommendation = $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            ['trade off 1'],
            ['risk 1'],
        );

        self::assertSame(Decision::SWITCH, $recommendation->decision());
        self::assertSame(DecisionReasonCode::CLEAR_SAVINGS, $recommendation->reasonCode());
        self::assertNotNull($recommendation->suggestedOfferVersionId());
        self::assertNotNull($recommendation->suggestedOfferSnapshot());
        self::assertNull($recommendation->waitKind());
        self::assertNull($recommendation->reviewTrigger());
        self::assertSame($alternative->estimatedImpact(), $recommendation->estimatedImpact());
    }

    public function test_low_fit_selected_alternative_cannot_build_switch(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = $this->buildOffer($offerId, 'Provider A', 'Fiber Plan 300');
        $offerVersion = $this->buildFiberVersion($offer, 300, '49.99');
        $alternative = $this->buildAlternative(
            $offerVersion->id(),
            FitLevel::LOW,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se permite SWITCH con fit LOW.');

        $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            [],
            [],
        );
    }

    public function test_high_friction_selected_alternative_cannot_build_switch(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = $this->buildOffer($offerId, 'Provider A', 'Fiber Plan 300');
        $offerVersion = $this->buildFiberVersion($offer, 300, '49.99');
        $alternative = $this->buildAlternative(
            $offerVersion->id(),
            FitLevel::HIGH,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::HIGH,
            false,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se permite SWITCH con fricción HIGH.');

        $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            [],
            [],
        );
    }

    public function test_unacceptable_trade_off_cannot_build_switch(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = $this->buildOffer($offerId, 'Provider A', 'Fiber Plan 300');
        $offerVersion = $this->buildFiberVersion($offer, 300, '49.99');
        $alternative = $this->buildAlternative(
            $offerVersion->id(),
            FitLevel::HIGH,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            true,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se permite SWITCH con trade-off no aceptado.');

        $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            [],
            [],
        );
    }

    public function test_snapshot_uses_commercial_data(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = new TelecomOffer(
            $offerId,
            'Test Provider',
            ProductType::FIBER_MOBILE,
            'Test Commercial Name',
            new DateTimeImmutable('2025-01-01'),
        );
        $commercialData = new TelecomOfferCommercialData(
            fiberSpeedMbps: 500,
            mobileDataDisplay: '50 GB',
            monthlyPrice: new Money('39.99', 'EUR'),
            mobileLinesIncluded: 2,
            tvIncluded: true,
            notes: null,
        );
        $normalizedData = new TelecomOfferNormalizedData(
            fiberCapacityBand: null,
            mobileUsageBandSupported: null,
            mobileLinesIncluded: 2,
            fiberIncluded: true,
            tvIncluded: true,
            asymmetricLines: false,
        );
        $offerVersion = TelecomOfferVersion::createForOffer(
            TelecomOfferVersionId::fromUuid(Uuid::v4()),
            $offer,
            1,
            'test-source',
            new DateTimeImmutable('2025-01-01'),
            null,
            null,
            $commercialData,
            $normalizedData,
            [],
        );
        $alternative = $this->buildAlternative(
            $offerVersion->id(),
            FitLevel::HIGH,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $recommendation = $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            [],
            [],
        );

        $snapshot = $recommendation->suggestedOfferSnapshot();

        self::assertNotNull($snapshot);
        self::assertSame($offer->provider(), $snapshot->provider());
        self::assertSame($offer->commercialName(), $snapshot->commercialName());
        self::assertSame($commercialData->monthlyPrice(), $snapshot->monthlyPrice());
        self::assertSame($commercialData->mobileLinesIncluded(), $snapshot->mobileLinesIncluded());
        self::assertSame($commercialData->tvIncluded(), $snapshot->tvIncluded());
        self::assertSame($commercialData->fiberSpeedMbps(), $snapshot->fiberSpeedMbps());
        self::assertSame($commercialData->mobileDataDisplay(), $snapshot->mobileDataDisplay());
    }

    public function test_mismatched_offer_version_is_rejected(): void
    {
        $builder = new SwitchRecommendationBuilder();
        $offerId = TelecomOfferId::fromUuid(Uuid::v4());
        $offer = $this->buildOffer($offerId, 'Provider A', 'Fiber Plan 300');
        $offerVersion = $this->buildFiberVersion($offer, 300, '49.99');
        $otherVersionId = TelecomOfferVersionId::fromUuid(Uuid::v4());
        $alternative = $this->buildAlternative(
            $otherVersionId,
            FitLevel::HIGH,
            '30',
            ImpactType::MONTHLY_SAVINGS,
            ChangeFriction::LOW,
            false,
        );

        $this->expectException(\InvalidArgumentException::class);

        $builder->buildSwitch(
            $alternative,
            $offer,
            $offerVersion,
            'main explanation',
            [],
            [],
        );
    }

    private function buildOffer(TelecomOfferId $id, string $provider, string $commercialName): TelecomOffer
    {
        return new TelecomOffer(
            $id,
            $provider,
            ProductType::FIBER,
            $commercialName,
            new DateTimeImmutable('2025-01-01'),
        );
    }

    private function buildFiberVersion(TelecomOffer $offer, int $fiberSpeed, string $monthlyPrice): TelecomOfferVersion
    {
        $commercialData = new TelecomOfferCommercialData(
            fiberSpeedMbps: $fiberSpeed,
            mobileDataDisplay: null,
            monthlyPrice: new Money($monthlyPrice, 'EUR'),
            mobileLinesIncluded: 0,
            tvIncluded: false,
            notes: null,
        );

        return $this->buildVersionWithCommercialData($offer, $commercialData);
    }

    private function buildVersionWithCommercialData(TelecomOffer $offer, TelecomOfferCommercialData $commercialData): TelecomOfferVersion
    {
        $normalizedData = new TelecomOfferNormalizedData(
            fiberCapacityBand: null,
            mobileUsageBandSupported: null,
            mobileLinesIncluded: $commercialData->mobileLinesIncluded(),
            fiberIncluded: true,
            tvIncluded: $commercialData->tvIncluded(),
            asymmetricLines: false,
        );

        return TelecomOfferVersion::createForOffer(
            TelecomOfferVersionId::fromUuid(Uuid::v4()),
            $offer,
            1,
            'test-source',
            new DateTimeImmutable('2025-01-01'),
            null,
            null,
            $commercialData,
            $normalizedData,
            [],
        );
    }

    private function buildAlternative(
        TelecomOfferVersionId $offerVersionId,
        FitLevel $fitLevel,
        string $savings,
        ImpactType $impactType,
        ChangeFriction $changeFriction,
        bool $hasUnacceptableTradeOff,
    ): AlternativeEvaluation {
        return new AlternativeEvaluation(
            $offerVersionId,
            $fitLevel,
            new EstimatedImpact(
                new Money($savings, 'EUR'),
                new Percentage('10'),
                $impactType,
                'impact summary',
            ),
            $changeFriction,
            $hasUnacceptableTradeOff,
        );
    }
}
