<?php

declare(strict_types=1);

namespace App\Advisor\Application\Service;

use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use Symfony\Component\Uid\Uuid;

final readonly class PublishedOfferEvaluationAssembler
{
    private const UUID_V5_NAMESPACE = '8f4e8a7a-5df6-4e2e-bd8f-4d6a7c3a9b21';

    public function assemble(PublishedOfferVersionForEvaluation $offer): EvaluablePublishedOffer
    {
        $sourceOfferVersionId = $offer->offerVersionId();
        $namespace = Uuid::fromString(self::UUID_V5_NAMESPACE);
        $stableUuid = Uuid::v5($namespace, 'PublishedOfferVersionForEvaluation:' . $sourceOfferVersionId);

        return new EvaluablePublishedOffer(
            sourceOfferVersionId: $sourceOfferVersionId,
            stableInternalOfferVersionId: TelecomOfferVersionId::fromUuid($stableUuid),
            provider: $offer->provider(),
            commercialName: $offer->commercialName(),
            productType: $offer->productType(),
            monthlyPrice: $offer->monthlyPrice(),
            fiberSpeedMbps: $offer->fiberSpeedMbps(),
            mobileDataDisplay: $offer->mobileDataDisplay(),
            mobileLinesIncluded: $offer->mobileLinesIncluded(),
            tvIncluded: $offer->tvIncluded(),
            fiberCapacityBand: $offer->fiberCapacityBand(),
            mobileUsageBandSupported: $offer->mobileUsageBandSupported(),
            fiberIncluded: $offer->fiberIncluded(),
            asymmetricLines: $offer->asymmetricLines(),
        );
    }

    public function createRecommendedOfferSnapshot(EvaluablePublishedOffer $offer): RecommendedOfferSnapshot
    {
        return new RecommendedOfferSnapshot(
            provider: $offer->provider(),
            commercialName: $offer->commercialName(),
            monthlyPrice: $offer->monthlyPrice(),
            mobileLinesIncluded: $offer->mobileLinesIncluded(),
            tvIncluded: $offer->tvIncluded(),
            fiberSpeedMbps: $offer->fiberSpeedMbps(),
            mobileDataDisplay: $offer->mobileDataDisplay(),
        );
    }
}
