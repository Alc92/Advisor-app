<?php

declare(strict_types=1);

namespace App\Advisor\Application\ViewModel;

use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\Decision;

final readonly class AssessmentResultViewModelMapper
{
    public function map(Assessment $assessment, AssessmentResult $result): AssessmentResultViewModel
    {
        $recommendation = $result->recommendation();
        $decision = $recommendation->decision();

        return new AssessmentResultViewModel(
            assessmentId: $assessment->id()->toString(),
            decision: $this->enumToScalar($decision),
            reasonCode: $this->enumToScalar($recommendation->reasonCode()),
            headline: $this->headlineFor($decision),
            mainExplanation: $recommendation->mainExplanation(),
            estimatedImpactSummary: $recommendation->estimatedImpact()->summary(),
            suggestedOffer: $decision === Decision::SWITCH
                ? $this->mapSuggestedOffer($recommendation->suggestedOfferSnapshot())
                : null,
            tradeOffs: $recommendation->tradeOffs(),
            risks: $recommendation->risks(),
            uncertaintySummary: $recommendation->uncertaintySummary(),
            analysisLimitations: array_map($this->enumToScalar(...), $recommendation->analysisLimitations()),
            waitKind: $recommendation->waitKind() !== null
                ? $this->enumToScalar($recommendation->waitKind())
                : null,
            recommendedReviewMoment: $recommendation->recommendedReviewMoment() !== null
                ? [
                    'year' => $recommendation->recommendedReviewMoment()->year(),
                    'month' => $recommendation->recommendedReviewMoment()->month(),
                ]
                : null,
            reviewTrigger: $recommendation->reviewTrigger() !== null
                ? $this->enumToScalar($recommendation->reviewTrigger())
                : null,
        );
    }

    private function headlineFor(Decision $decision): string
    {
        return match ($decision) {
            Decision::SWITCH => 'Te compensa cambiar',
            Decision::WAIT => 'Te conviene esperar',
            Decision::STAY => 'No compensa cambiar ahora',
        };
    }

    /**
     * @return array{
     *   provider:string,
     *   commercialName:string,
     *   monthlyPriceAmount:string,
     *   monthlyPriceCurrency:string,
     *   fiberSpeedMbps:int|null,
     *   mobileDataDisplay:string|null,
     *   mobileLinesIncluded:int,
     *   tvIncluded:bool
     * }
     */
    private function mapSuggestedOffer(?RecommendedOfferSnapshot $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        return [
            'provider' => $snapshot->provider(),
            'commercialName' => $snapshot->commercialName(),
            'monthlyPriceAmount' => $snapshot->monthlyPrice()->amount(),
            'monthlyPriceCurrency' => $snapshot->monthlyPrice()->currency(),
            'fiberSpeedMbps' => $snapshot->fiberSpeedMbps(),
            'mobileDataDisplay' => $snapshot->mobileDataDisplay(),
            'mobileLinesIncluded' => $snapshot->mobileLinesIncluded(),
            'tvIncluded' => $snapshot->tvIncluded(),
        ];
    }

    private function enumToScalar(\UnitEnum $enum): string
    {
        if ($enum instanceof \BackedEnum) {
            return (string) $enum->value;
        }

        return $enum->name;
    }
}
