<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Service;

use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Application\Service\EvaluablePublishedOffer;
use App\Advisor\Application\Service\PublishedOfferEvaluationAssembler;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use PHPUnit\Framework\TestCase;

final class PublishedOfferEvaluationAssemblerTest extends TestCase
{
    public function test_it_converts_mobile_published_offer_to_evaluable_offer(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();
        $offer = $this->mobileOffer('source-mobile-1');

        $result = $assembler->assemble($offer);

        self::assertInstanceOf(EvaluablePublishedOffer::class, $result);
        self::assertSame('source-mobile-1', $result->sourceOfferVersionId());
        self::assertSame('Provider Mobile', $result->provider());
        self::assertSame('Plan Mobile 50GB', $result->commercialName());
        self::assertSame(ProductType::MOBILE, $result->productType());
        self::assertSame('19.90', $result->monthlyPrice()->amount());
        self::assertSame('EUR', $result->monthlyPrice()->currency());
        self::assertNull($result->fiberSpeedMbps());
        self::assertSame('50 GB', $result->mobileDataDisplay());
        self::assertSame(1, $result->mobileLinesIncluded());
        self::assertFalse($result->tvIncluded());
        self::assertNull($result->fiberCapacityBand());
        self::assertSame(MobileUsageBand::MEDIUM, $result->mobileUsageBandSupported());
        self::assertFalse($result->fiberIncluded());
        self::assertFalse($result->asymmetricLines());
    }

    public function test_it_converts_fiber_mobile_published_offer_to_evaluable_offer(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();
        $offer = $this->fiberMobileOffer('source-fm-1');

        $result = $assembler->assemble($offer);

        self::assertSame('source-fm-1', $result->sourceOfferVersionId());
        self::assertSame(ProductType::FIBER_MOBILE, $result->productType());
        self::assertSame('Provider Combo', $result->provider());
        self::assertSame('Fibra 600 + 2 lineas', $result->commercialName());
        self::assertSame('49.00', $result->monthlyPrice()->amount());
        self::assertSame(600, $result->fiberSpeedMbps());
        self::assertSame('100 GB compartidos', $result->mobileDataDisplay());
        self::assertSame(2, $result->mobileLinesIncluded());
        self::assertTrue($result->tvIncluded());
        self::assertSame(FiberCapacityBand::HIGH, $result->fiberCapacityBand());
        self::assertSame(MobileUsageBand::HIGH, $result->mobileUsageBandSupported());
        self::assertTrue($result->fiberIncluded());
        self::assertFalse($result->asymmetricLines());
    }

    public function test_it_creates_recommended_offer_snapshot_from_evaluable_offer(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();
        $evaluable = $assembler->assemble($this->fiberMobileOffer('source-fm-snapshot'));

        $snapshot = $assembler->createRecommendedOfferSnapshot($evaluable);

        self::assertInstanceOf(RecommendedOfferSnapshot::class, $snapshot);
        self::assertSame($evaluable->provider(), $snapshot->provider());
        self::assertSame($evaluable->commercialName(), $snapshot->commercialName());
        self::assertSame($evaluable->monthlyPrice(), $snapshot->monthlyPrice());
        self::assertSame($evaluable->mobileLinesIncluded(), $snapshot->mobileLinesIncluded());
        self::assertSame($evaluable->tvIncluded(), $snapshot->tvIncluded());
        self::assertSame($evaluable->fiberSpeedMbps(), $snapshot->fiberSpeedMbps());
        self::assertSame($evaluable->mobileDataDisplay(), $snapshot->mobileDataDisplay());
    }

    public function test_it_generates_stable_internal_id_for_same_source_offer_version_id(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();

        $first = $assembler->assemble($this->mobileOffer('same-source-id'));
        $second = $assembler->assemble($this->mobileOffer('same-source-id'));

        self::assertSame(
            $first->stableInternalOfferVersionId()->toString(),
            $second->stableInternalOfferVersionId()->toString(),
        );
    }

    public function test_it_generates_different_internal_ids_for_different_source_offer_version_ids(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();

        $first = $assembler->assemble($this->mobileOffer('source-id-a'));
        $second = $assembler->assemble($this->mobileOffer('source-id-b'));

        self::assertNotSame(
            $first->stableInternalOfferVersionId()->toString(),
            $second->stableInternalOfferVersionId()->toString(),
        );
    }

    public function test_it_preserves_asymmetric_offer_flag_without_rebuilding_catalog_entity(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();

        $result = $assembler->assemble($this->asymmetricFiberMobileOffer('source-asym-1'));

        self::assertTrue($result->asymmetricLines());
    }

    public function test_it_preserves_original_source_offer_version_id(): void
    {
        $assembler = new PublishedOfferEvaluationAssembler();

        $result = $assembler->assemble($this->mobileOffer('ofv_gate1_mobile_basic_v1'));

        self::assertSame('ofv_gate1_mobile_basic_v1', $result->sourceOfferVersionId());
    }

    private function mobileOffer(string $sourceOfferVersionId): PublishedOfferVersionForEvaluation
    {
        return new PublishedOfferVersionForEvaluation(
            offerVersionId: $sourceOfferVersionId,
            provider: 'Provider Mobile',
            commercialName: 'Plan Mobile 50GB',
            monthlyPrice: new Money('19.90', 'EUR'),
            mobileLinesIncluded: 1,
            tvIncluded: false,
            fiberSpeedMbps: null,
            mobileDataDisplay: '50 GB',
            productType: ProductType::MOBILE,
            fiberCapacityBand: null,
            mobileUsageBandSupported: MobileUsageBand::MEDIUM,
            fiberIncluded: false,
            asymmetricLines: false,
        );
    }

    private function fiberMobileOffer(string $sourceOfferVersionId): PublishedOfferVersionForEvaluation
    {
        return new PublishedOfferVersionForEvaluation(
            offerVersionId: $sourceOfferVersionId,
            provider: 'Provider Combo',
            commercialName: 'Fibra 600 + 2 lineas',
            monthlyPrice: new Money('49.00', 'EUR'),
            mobileLinesIncluded: 2,
            tvIncluded: true,
            fiberSpeedMbps: 600,
            mobileDataDisplay: '100 GB compartidos',
            productType: ProductType::FIBER_MOBILE,
            fiberCapacityBand: FiberCapacityBand::HIGH,
            mobileUsageBandSupported: MobileUsageBand::HIGH,
            fiberIncluded: true,
            asymmetricLines: false,
        );
    }

    private function asymmetricFiberMobileOffer(string $sourceOfferVersionId): PublishedOfferVersionForEvaluation
    {
        return new PublishedOfferVersionForEvaluation(
            offerVersionId: $sourceOfferVersionId,
            provider: 'Provider Asymmetric',
            commercialName: 'Fibra 600 + 3 lineas asimetricas',
            monthlyPrice: new Money('45.00', 'EUR'),
            mobileLinesIncluded: 3,
            tvIncluded: false,
            fiberSpeedMbps: 600,
            mobileDataDisplay: '50GB + 10GB + 10GB',
            productType: ProductType::FIBER_MOBILE,
            fiberCapacityBand: FiberCapacityBand::HIGH,
            mobileUsageBandSupported: MobileUsageBand::MEDIUM,
            fiberIncluded: true,
            asymmetricLines: true,
        );
    }
}
