<?php

declare(strict_types=1);

namespace App\Advisor\Application\ViewModel;

use InvalidArgumentException;

final readonly class AssessmentResultViewModel
{
    public string $assessmentId;
    public bool $isPersistedFunctionally;
    public string $decision;
    public string $reasonCode;
    public string $headline;
    public string $mainExplanation;
    public ?string $estimatedImpactSummary;
    /** @var array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string, fiberSpeedMbps:int|null, mobileDataDisplay:string|null, mobileLinesIncluded:int, tvIncluded:bool}|null */
    public ?array $suggestedOffer;
    /** @var list<string> */
    public array $tradeOffs;
    /** @var list<string> */
    public array $risks;
    public ?string $uncertaintySummary;
    /** @var list<string> */
    public array $analysisLimitations;
    public ?string $waitKind;
    /** @var array{year:int, month:int}|null */
    public ?array $recommendedReviewMoment;
    public ?string $reviewTrigger;

    /**
     * @param array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string, fiberSpeedMbps:int|null, mobileDataDisplay:string|null, mobileLinesIncluded:int, tvIncluded:bool}|null $suggestedOffer
     * @param list<string> $tradeOffs
     * @param list<string> $risks
     * @param list<string> $analysisLimitations
     * @param array{year:int, month:int}|null $recommendedReviewMoment
     */
    public function __construct(
        string $assessmentId,
        string $decision,
        string $reasonCode,
        string $headline,
        string $mainExplanation,
        ?string $estimatedImpactSummary,
        ?array $suggestedOffer,
        array $tradeOffs,
        array $risks,
        ?string $uncertaintySummary,
        array $analysisLimitations,
        ?string $waitKind,
        ?array $recommendedReviewMoment,
        ?string $reviewTrigger,
    ) {
        $this->assessmentId = $assessmentId;
        $this->decision = $decision;
        $this->reasonCode = $reasonCode;
        $this->headline = $headline;
        $this->mainExplanation = $mainExplanation;
        $this->estimatedImpactSummary = $estimatedImpactSummary;
        $this->suggestedOffer = $this->validateSuggestedOffer($suggestedOffer);
        $this->tradeOffs = $tradeOffs;
        $this->risks = $risks;
        $this->uncertaintySummary = $uncertaintySummary;
        $this->analysisLimitations = $analysisLimitations;
        $this->waitKind = $waitKind;
        $this->recommendedReviewMoment = $this->validateRecommendedReviewMoment($recommendedReviewMoment);
        $this->reviewTrigger = $reviewTrigger;
        $this->isPersistedFunctionally = false;
    }

    /**
     * @param array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string, fiberSpeedMbps:int|null, mobileDataDisplay:string|null, mobileLinesIncluded:int, tvIncluded:bool}|null $suggestedOffer
     * @return array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string, fiberSpeedMbps:int|null, mobileDataDisplay:string|null, mobileLinesIncluded:int, tvIncluded:bool}|null
     */
    private function validateSuggestedOffer(?array $suggestedOffer): ?array
    {
        if ($suggestedOffer === null) {
            return null;
        }

        $expectedKeys = ['provider', 'commercialName', 'monthlyPriceAmount', 'monthlyPriceCurrency', 'fiberSpeedMbps', 'mobileDataDisplay', 'mobileLinesIncluded', 'tvIncluded'];
        $actualKeys = array_keys($suggestedOffer);
        sort($expectedKeys);
        sort($actualKeys);

        if ($actualKeys !== $expectedKeys) {
            throw new InvalidArgumentException('suggestedOffer has invalid keys.');
        }

        if (!is_string($suggestedOffer['provider'])
            || !is_string($suggestedOffer['commercialName'])
            || !is_string($suggestedOffer['monthlyPriceAmount'])
            || !is_string($suggestedOffer['monthlyPriceCurrency'])
        ) {
            throw new InvalidArgumentException('suggestedOffer string fields are invalid.');
        }

        if ($suggestedOffer['fiberSpeedMbps'] !== null && !is_int($suggestedOffer['fiberSpeedMbps'])) {
            throw new InvalidArgumentException('suggestedOffer fiberSpeedMbps must be int|null.');
        }

        if ($suggestedOffer['mobileDataDisplay'] !== null && !is_string($suggestedOffer['mobileDataDisplay'])) {
            throw new InvalidArgumentException('suggestedOffer mobileDataDisplay must be string|null.');
        }

        if (!is_int($suggestedOffer['mobileLinesIncluded'])) {
            throw new InvalidArgumentException('suggestedOffer mobileLinesIncluded must be int.');
        }

        if (!is_bool($suggestedOffer['tvIncluded'])) {
            throw new InvalidArgumentException('suggestedOffer tvIncluded must be bool.');
        }

        return $suggestedOffer;
    }

    /**
     * @param array{year:int, month:int}|null $recommendedReviewMoment
     * @return array{year:int, month:int}|null
     */
    private function validateRecommendedReviewMoment(?array $recommendedReviewMoment): ?array
    {
        if ($recommendedReviewMoment === null) {
            return null;
        }

        $expectedKeys = ['month', 'year'];
        $actualKeys = array_keys($recommendedReviewMoment);
        sort($actualKeys);

        if ($actualKeys !== $expectedKeys) {
            throw new InvalidArgumentException('recommendedReviewMoment must contain exactly year and month.');
        }

        if (!is_int($recommendedReviewMoment['year']) || !is_int($recommendedReviewMoment['month'])) {
            throw new InvalidArgumentException('recommendedReviewMoment year and month must be integers.');
        }

        return $recommendedReviewMoment;
    }
}
