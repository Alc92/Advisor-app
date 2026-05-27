<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\ViewModel;

use App\Advisor\Application\ViewModel\AssessmentResultViewModel;
use PHPUnit\Framework\TestCase;

final class AssessmentResultViewModelTest extends TestCase
{
    public function testAssessmentResultViewModelCanBeCreatedForSwitchOutputIncludingSuggestedOffer(): void
    {
        $viewModel = new AssessmentResultViewModel(
            assessmentId: 'a1',
            decision: 'SWITCH',
            reasonCode: 'BETTER_PRICE',
            headline: 'Switch now',
            mainExplanation: 'You can save money.',
            estimatedImpactSummary: 'Approx. 10 EUR monthly savings.',
            suggestedOffer: [
                'provider' => 'Provider X',
                'commercialName' => 'Plan Plus',
                'monthlyPriceAmount' => '39.99',
                'monthlyPriceCurrency' => 'EUR',
            ],
            tradeOffs: ['12-month commitment'],
            risks: ['Price may change after promo'],
            uncertaintySummary: 'Estimated from user input',
            analysisLimitations: ['No invoice data'],
            waitKind: null,
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );

        self::assertSame('SWITCH', $viewModel->decision);
        self::assertFalse($viewModel->isPersistedFunctionally);
        self::assertSame('Provider X', $viewModel->suggestedOffer['provider']);
        self::assertSame('Plan Plus', $viewModel->suggestedOffer['commercialName']);
        self::assertSame('39.99', $viewModel->suggestedOffer['monthlyPriceAmount']);
        self::assertSame('EUR', $viewModel->suggestedOffer['monthlyPriceCurrency']);
        self::assertArrayNotHasKey('productName', $viewModel->suggestedOffer);
    }

    public function testAssessmentResultViewModelCanBeCreatedForWaitOutputIncludingWaitMetadata(): void
    {
        $viewModel = new AssessmentResultViewModel(
            assessmentId: 'a2',
            decision: 'WAIT',
            reasonCode: 'PROMO_END_SOON',
            headline: 'Wait for promo end',
            mainExplanation: 'Current promotion still applies.',
            estimatedImpactSummary: null,
            suggestedOffer: null,
            tradeOffs: [],
            risks: [],
            uncertaintySummary: null,
            analysisLimitations: ['Limited market sample'],
            waitKind: 'PROMOTION_WINDOW',
            recommendedReviewMoment: ['year' => 2027, 'month' => 3],
            reviewTrigger: 'Promotion expiry',
        );

        self::assertSame('WAIT', $viewModel->decision);
        self::assertNull($viewModel->suggestedOffer);
        self::assertNotNull($viewModel->waitKind);
        self::assertSame(2027, $viewModel->recommendedReviewMoment['year']);
        self::assertSame(3, $viewModel->recommendedReviewMoment['month']);
        self::assertNotNull($viewModel->reviewTrigger);
        self::assertFalse($viewModel->isPersistedFunctionally);
    }

    public function testAssessmentResultViewModelCanBeCreatedForStayOutputWithoutSuggestedOffer(): void
    {
        $viewModel = new AssessmentResultViewModel(
            assessmentId: 'a3',
            decision: 'STAY',
            reasonCode: 'NO_CLEAR_IMPROVEMENT',
            headline: 'Stay with current plan',
            mainExplanation: 'No better option found in curated catalog.',
            estimatedImpactSummary: null,
            suggestedOffer: null,
            tradeOffs: ['Potentially miss short-term promo'],
            risks: [],
            uncertaintySummary: null,
            analysisLimitations: [],
            waitKind: null,
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );

        self::assertSame('STAY', $viewModel->decision);
        self::assertNull($viewModel->suggestedOffer);
        self::assertNull($viewModel->waitKind);
        self::assertNull($viewModel->recommendedReviewMoment);
        self::assertFalse($viewModel->isPersistedFunctionally);
    }

    public function testAssessmentResultViewModelDoesNotExposeDomainObjects(): void
    {
        $viewModel = new AssessmentResultViewModel(
            assessmentId: 'a4',
            decision: 'STAY',
            reasonCode: 'GOOD_ENOUGH',
            headline: 'Stay for now',
            mainExplanation: 'Current plan remains competitive.',
            estimatedImpactSummary: null,
            suggestedOffer: null,
            tradeOffs: ['Could miss short promotions'],
            risks: ['Market can change quickly'],
            uncertaintySummary: 'Based on declared usage bands.',
            analysisLimitations: ['No billing history loaded'],
            waitKind: null,
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );

        self::assertIsString($viewModel->assessmentId);
        self::assertIsString($viewModel->decision);
        self::assertIsArray($viewModel->tradeOffs);
        self::assertIsArray($viewModel->risks);
        self::assertIsArray($viewModel->analysisLimitations);
        self::assertIsBool($viewModel->isPersistedFunctionally);
        self::assertNull($viewModel->suggestedOffer);
        self::assertNull($viewModel->recommendedReviewMoment);
    }
}
