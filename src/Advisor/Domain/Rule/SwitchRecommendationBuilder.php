<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\ImpactType;
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

        $impact = $selectedAlternative->estimatedImpact();

        if ($impact->impactType() !== ImpactType::MONTHLY_SAVINGS || $impact->monthlySavingsEstimate() === null || $this->amountToCents($impact->monthlySavingsEstimate()->amount()) < 500) {
            throw new InvalidArgumentException('No se permite SWITCH sin ahorro mensual suficiente (mínimo 5 EUR).');
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

    /**
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function buildSwitchFromSnapshot(
        AlternativeEvaluation $selectedAlternative,
        RecommendedOfferSnapshot $suggestedOfferSnapshot,
        array $analysisLimitations = [],
    ): Recommendation {
        if ($selectedAlternative->fitLevel() === FitLevel::LOW) {
            throw new InvalidArgumentException('No se permite SWITCH con fit LOW.');
        }

        if ($selectedAlternative->changeFriction() === ChangeFriction::HIGH) {
            throw new InvalidArgumentException('No se permite SWITCH con fricción HIGH.');
        }

        if ($selectedAlternative->hasUnacceptableTradeOff() === true) {
            throw new InvalidArgumentException('No se permite SWITCH con trade-off no aceptado.');
        }

        $impact = $selectedAlternative->estimatedImpact();

        if ($impact->impactType() !== ImpactType::MONTHLY_SAVINGS || $impact->monthlySavingsEstimate() === null || $this->amountToCents($impact->monthlySavingsEstimate()->amount()) < 500) {
            throw new InvalidArgumentException('No se permite SWITCH sin ahorro mensual suficiente (mínimo 5 EUR).');
        }

        return new Recommendation(
            decision: Decision::SWITCH,
            reasonCode: DecisionReasonCode::CLEAR_SAVINGS,
            waitKind: null,
            suggestedOfferVersionId: $selectedAlternative->offerVersionId()->value(),
            suggestedOfferSnapshot: $suggestedOfferSnapshot,
            estimatedImpact: $impact,
            mainExplanation: 'Se detecta una alternativa con ahorro claro y encaje suficiente.',
            tradeOffs: [],
            risks: [],
            uncertaintySummary: null,
            analysisLimitations: $analysisLimitations,
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }

    private function amountToCents(string $amount): int
    {
        $normalized = trim($amount);
        $parts = explode('.', $normalized, 2);
        $units = (int) $parts[0];
        $decimals = isset($parts[1]) ? (int) str_pad(substr($parts[1], 0, 2), 2, '0') : 0;

        return ($units * 100) + $decimals;
    }
}
