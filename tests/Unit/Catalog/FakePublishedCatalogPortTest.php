<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedCatalogPort;
use App\Advisor\Domain\Enum\ProductType;
use App\Tests\Support\Advisor\FakePublishedCatalogPort;
use PHPUnit\Framework\TestCase;

final class FakePublishedCatalogPortTest extends TestCase
{
    public function testFakePublishedCatalogPortReturnsGateOnePublishedCatalog(): void
    {
        $fake = new FakePublishedCatalogPort();

        self::assertInstanceOf(PublishedCatalogPort::class, $fake);
        self::assertSame(0, $fake->calls());

        $catalog = $fake->getCurrentPublishedCatalog();

        self::assertInstanceOf(PublishedCatalogForEvaluation::class, $catalog);
        self::assertSame('catpub_gate1_001', $catalog->publicationId());
        self::assertSame('gate1-fixture-2026-05-31', $catalog->publicationVersion());
        self::assertSame(1, $fake->calls());
    }

    public function testFakeCatalogContainsTheSevenClosedOffers(): void
    {
        $catalog = (new FakePublishedCatalogPort())->getCurrentPublishedCatalog();

        $offerVersionIds = array_map(
            static fn ($offer): string => $offer->offerVersionId(),
            $catalog->offerVersions(),
        );

        self::assertCount(7, $offerVersionIds);
        self::assertSame([
            'ofv_gate1_mobile_basic_v1',
            'ofv_gate1_mobile_unlimited_v1',
            'ofv_gate1_fiber_300_v1',
            'ofv_gate1_fiber_600_tv_v1',
            'ofv_gate1_bundle_300_1line_v1',
            'ofv_gate1_bundle_600_2lines_tv_v1',
            'ofv_gate1_bundle_asymmetric_3lines_v1',
        ], $offerVersionIds);
    }

    public function testFakeCatalogCoversRequiredProductDiversity(): void
    {
        $offers = (new FakePublishedCatalogPort())->getCurrentPublishedCatalog()->offerVersions();

        self::assertTrue($this->hasOfferOfType($offers, ProductType::MOBILE));
        self::assertTrue($this->hasOfferOfType($offers, ProductType::FIBER));
        self::assertTrue($this->hasOfferOfType($offers, ProductType::FIBER_MOBILE));
        self::assertTrue($this->hasOfferWithTvIncluded($offers, true));
        self::assertTrue($this->hasOfferWithTvIncluded($offers, false));
        self::assertTrue($this->hasOfferWithAsymmetricLines($offers));
    }

    public function testFakeCatalogContainsAsymmetricOffer(): void
    {
        $offers = (new FakePublishedCatalogPort())->getCurrentPublishedCatalog()->offerVersions();

        $asymmetricOffer = null;
        foreach ($offers as $offer) {
            if ($offer->offerVersionId() === 'ofv_gate1_bundle_asymmetric_3lines_v1') {
                $asymmetricOffer = $offer;
                break;
            }
        }

        self::assertNotNull($asymmetricOffer);
        self::assertSame(3, $asymmetricOffer->mobileLinesIncluded());
        self::assertTrue($asymmetricOffer->asymmetricLines());
        self::assertSame('50GB + 10GB + 10GB', $asymmetricOffer->mobileDataDisplay());
    }

    public function testFakeCatalogContainsCoherentCommercialData(): void
    {
        $offers = (new FakePublishedCatalogPort())->getCurrentPublishedCatalog()->offerVersions();

        foreach ($offers as $offer) {
            self::assertNotSame('', trim($offer->provider()));
            self::assertNotSame('', trim($offer->commercialName()));
            self::assertNotSame('', trim($offer->monthlyPrice()->amount()));
            self::assertSame('EUR', $offer->monthlyPrice()->currency());
            self::assertNotSame('', trim($offer->offerVersionId()));
            self::assertContains($offer->productType(), [
                ProductType::MOBILE,
                ProductType::FIBER,
                ProductType::FIBER_MOBILE,
            ]);
        }
    }

    private function hasOfferOfType(array $offers, ProductType $productType): bool
    {
        foreach ($offers as $offer) {
            if ($offer->productType() === $productType) {
                return true;
            }
        }

        return false;
    }

    private function hasOfferWithTvIncluded(array $offers, bool $tvIncluded): bool
    {
        foreach ($offers as $offer) {
            if ($offer->tvIncluded() === $tvIncluded) {
                return true;
            }
        }

        return false;
    }

    private function hasOfferWithAsymmetricLines(array $offers): bool
    {
        foreach ($offers as $offer) {
            if ($offer->asymmetricLines()) {
                return true;
            }
        }

        return false;
    }
}
