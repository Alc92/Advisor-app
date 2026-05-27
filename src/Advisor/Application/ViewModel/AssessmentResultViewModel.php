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
    /** @var array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string}|null */
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
     * @param array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string}|null $suggestedOffer
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
        $this->assessmentId = $this->requireNonEmpty($assessmentId, 'assessmentId');
        $this->decision = $this->requireNonEmpty($decision, 'decision');
        $this->reasonCode = $this->requireNonEmpty($reasonCode, 'reasonCode');
        $this->headline = $this->requireNonEmpty($headline, 'headline');
        $this->mainExplanation = $this->requireNonEmpty($mainExplanation, 'mainExplanation');
        $this->estimatedImpactSummary = $estimatedImpactSummary;
        $this->suggestedOffer = $this->validateSuggestedOffer($suggestedOffer);
        $this->tradeOffs = $this->validateStringList($tradeOffs, 'tradeOffs');
        $this->risks = $this->validateStringList($risks, 'risks');
        $this->uncertaintySummary = $uncertaintySummary;
        $this->analysisLimitations = $this->validateStringList($analysisLimitations, 'analysisLimitations');
        $this->waitKind = $waitKind;
        $this->recommendedReviewMoment = $this->validateRecommendedReviewMoment($recommendedReviewMoment);
        $this->reviewTrigger = $reviewTrigger;
        $this->isPersistedFunctionally = false;
    }

    private function requireNonEmpty(string $value, string $field): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException($field . ' cannot be empty.');
        }

        return $trimmed;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function validateStringList(array $values, string $field): array
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException($field . ' must contain only strings.');
            }
        }

        return array_values($values);
    }

    /**
     * @param array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string}|null $suggestedOffer
     * @return array{provider:string, commercialName:string, monthlyPriceAmount:string, monthlyPriceCurrency:string}|null
     */
    private function validateSuggestedOffer(?array $suggestedOffer): ?array
    {
        if ($suggestedOffer === null) {
            return null;
        }

        $expectedKeys = ['provider', 'commercialName', 'monthlyPriceAmount', 'monthlyPriceCurrency'];
        $actualKeys = array_keys($suggestedOffer);
        sort($expectedKeys);
        sort($actualKeys);

        if ($actualKeys !== $expectedKeys) {
            throw new InvalidArgumentException('suggestedOffer must contain exactly provider, commercialName, monthlyPriceAmount, monthlyPriceCurrency.');
        }

        foreach ($suggestedOffer as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('suggestedOffer values must be strings.');
            }
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
