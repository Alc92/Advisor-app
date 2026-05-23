<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Catalog\Domain\TelecomOffer;
use App\Catalog\Domain\TelecomOfferVersion;
use InvalidArgumentException;

final class SwitchRecommendationBuilder
{
    /**
     * @param list<string> $tradeOffs
     * @param list<string> $risks
     */
    public function buildSwitch(
        AlternativeEvaluation $selectedAlternative,
        TelecomOffer $offer,
        TelecomOfferVersion $offerVersion,
        string $mainExplanation,
        array $tradeOffs,
        array $risks,
    ): Recommendation {
        if ($offerVersion->offerId()->toString() !== $offer->id()->toString()) {
            throw new InvalidArgumentException('Offer version does not belong to the given offer.');
        }

        if ($selectedAlternative->offerVersionId()->toString() !== $offerVersion->id()->toString()) {
            throw new InvalidArgumentException('Selected alternative does not match the given offer version.');
        }

        if ($selectedAlternative->fitLevel() === FitLevel::LOW) {
            throw new InvalidArgumentException('No se permite SWITCH con fit LOW.');
        }

        if ($selectedAlternative->changeFriction() === ChangeFriction::HIGH) {
            throw new InvalidArgumentException('No se permite SWITCH con fricción HIGH.');
        }

        if ($selectedAlternative->hasUnacceptableTradeOff() === true) {
            throw new InvalidArgumentException('No se permite SWITCH con trade-off no aceptado.');
        }

        $commercialData = $offerVersion->commercialData();

        $snapshot = new RecommendedOfferSnapshot(
            provider: $offer->provider(),
            commercialName: $offer->commercialName(),
            monthlyPrice: $commercialData->monthlyPrice(),
            mobileLinesIncluded: $commercialData->mobileLinesIncluded(),
            tvIncluded: $commercialData->tvIncluded(),
            fiberSpeedMbps: $commercialData->fiberSpeedMbps(),
            mobileDataDisplay: $commercialData->mobileDataDisplay(),
        );

        return new Recommendation(
            decision: Decision::SWITCH,
            reasonCode: DecisionReasonCode::CLEAR_SAVINGS,
            waitKind: null,
            suggestedOfferVersionId: $offerVersion->id()->value(),
            suggestedOfferSnapshot: $snapshot,
            estimatedImpact: $selectedAlternative->estimatedImpact(),
            mainExplanation: $mainExplanation,
            tradeOffs: $tradeOffs,
            risks: $risks,
            uncertaintySummary: null,
            analysisLimitations: [],
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }
}
