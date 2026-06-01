<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Service;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Application\Service\AssessmentEvaluator;
use App\Advisor\Application\Service\PublishedOfferEvaluationAssembler;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\Rule\AlternativeSelector;
use App\Advisor\Domain\Rule\ChangeFrictionCalculator;
use App\Advisor\Domain\Rule\EstimatedImpactCalculator;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\Rule\FitCalculator;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\Rule\StayRecommendationBuilder;
use App\Advisor\Domain\Rule\SwitchRecommendationBuilder;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use PHPUnit\Framework\TestCase;

final class AssessmentEvaluatorTest extends TestCase
{
    public function test_it_returns_wait_when_applicable_usage_is_missing(): void
    {
        $evaluator = $this->evaluator();
        $snapshot = $this->snapshotForMobileWithMissingUsage();

        $result = $evaluator->evaluate($snapshot, null);

        self::assertSame(Decision::WAIT, $result->recommendation()->decision());
    }

    public function test_it_does_not_require_catalog_when_minimum_input_is_not_met(): void
    {
        $evaluator = $this->evaluator();
        $snapshot = $this->snapshotForMobileWithMissingUsage();

        $result = $evaluator->evaluate($snapshot, null);

        self::assertSame(Decision::WAIT, $result->recommendation()->decision());
        self::assertNotNull($result->evaluationTrace());
    }

    public function test_it_sets_wait_reason_kind_trigger_and_limitation_for_insufficient_minimum(): void
    {
        $evaluator = $this->evaluator();
        $snapshot = $this->snapshotForMobileWithMissingUsage();

        $recommendation = $evaluator->evaluate($snapshot, null)->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
        self::assertContains(AnalysisLimitationCode::MISSING_CRITICAL_DATA, $recommendation->analysisLimitations());
    }

    public function test_it_sets_not_evaluated_minimum_not_met_trace_when_trace_can_be_built(): void
    {
        $evaluator = $this->evaluator();
        $snapshot = $this->snapshotForMobileWithMissingUsage();

        $trace = $evaluator->evaluate($snapshot, null)->evaluationTrace();

        self::assertNotNull($trace);
        self::assertSame(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET, $trace->evaluationMode());
        self::assertSame([], $trace->evaluatedOfferVersionIds());
        self::assertSame([], $trace->hardFilteredOffers());
        self::assertSame([], $trace->rankedOutOffers());
        self::assertNull($trace->selectedOfferVersionId());
        self::assertNull($trace->selectedFitLevel());
        self::assertNull($trace->selectedFriction());
        self::assertContains(AnalysisLimitationCode::MISSING_CRITICAL_DATA, $trace->analysisLimitations());
    }

    public function test_it_returns_switch_for_clear_savings_with_valid_catalog(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00'),
            $this->catalogWith($this->mobileOffer('offer-switch', 'Provider Switch', 'Mobile Switch', '30.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::SWITCH, $recommendation->decision());
        self::assertSame(DecisionReasonCode::CLEAR_SAVINGS, $recommendation->reasonCode());
        self::assertNotNull($recommendation->suggestedOfferVersionId());
        self::assertNotNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $result->evaluationTrace()?->evaluationMode());
        self::assertNotNull($result->evaluationTrace()?->selectedOfferVersionId());
    }

    public function test_it_returns_wait_for_active_commitment_with_catalog_evaluated(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00', CommitmentStatus::YES, new ApproximateDate(2026, 6)),
            $this->catalogWith($this->mobileOffer('offer-wait', 'Provider Wait', 'Mobile Wait', '30.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_FOR_COMMITMENT_END, $recommendation->reasonCode());
        self::assertSame(WaitKind::TIMING, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::COMMITMENT_END, $recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(EvaluationMode::EVALUATED_DEGRADED, $result->evaluationTrace()?->evaluationMode());
    }

    public function test_it_returns_stay_for_active_commitment_when_no_offer_has_sufficient_improvement(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00', CommitmentStatus::YES, new ApproximateDate(2026, 6)),
            $this->catalogWith($this->mobileOffer('offer-stay-commitment', 'Provider Stay Commitment', 'Mobile Stay Commitment', '48.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::NO_CLEAR_IMPROVEMENT, $recommendation->reasonCode());
        self::assertNull($recommendation->waitKind());
        self::assertNull($recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $result->evaluationTrace()?->evaluationMode());
    }

    public function test_it_returns_stay_when_no_offer_has_sufficient_improvement(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00'),
            $this->catalogWith($this->mobileOffer('offer-stay', 'Provider Stay', 'Mobile Stay', '48.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::NO_CLEAR_IMPROVEMENT, $recommendation->reasonCode());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $result->evaluationTrace()?->evaluationMode());
    }

    public function test_it_returns_wait_for_unknown_commitment_when_switch_candidate_exists(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(
                MobileUsageBand::HIGH,
                '50.00',
                CommitmentStatus::UNKNOWN,
                null,
                false,
                PromotionStatus::NOT_ACTIVE,
            ),
            $this->catalogWith($this->mobileOffer('offer-unknown-commitment', 'Provider Uncertain', 'Mobile Uncertain', '30.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
    }

    public function test_it_returns_wait_for_unknown_promotion_when_switch_candidate_exists(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(
                MobileUsageBand::HIGH,
                '50.00',
                CommitmentStatus::NO,
                null,
                false,
                PromotionStatus::UNKNOWN,
            ),
            $this->catalogWith($this->mobileOffer('offer-unknown-promotion', 'Provider Uncertain', 'Mobile Uncertain', '30.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
    }

    public function test_it_keeps_stay_for_relevant_uncertainty_when_no_offer_has_sufficient_improvement(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(
                MobileUsageBand::HIGH,
                '50.00',
                CommitmentStatus::UNKNOWN,
                null,
                false,
                PromotionStatus::NOT_ACTIVE,
            ),
            $this->catalogWith($this->mobileOffer('offer-stay-uncertain', 'Provider Stay', 'Mobile Stay', '48.00', MobileUsageBand::HIGH)),
        );

        $recommendation = $result->recommendation();

        self::assertSame(Decision::STAY, $recommendation->decision());
        self::assertSame(DecisionReasonCode::NO_CLEAR_IMPROVEMENT, $recommendation->reasonCode());
        self::assertNull($recommendation->waitKind());
        self::assertNull($recommendation->reviewTrigger());
        self::assertNull($recommendation->suggestedOfferVersionId());
        self::assertNull($recommendation->suggestedOfferSnapshot());
    }

    public function test_it_marks_trace_as_degraded_by_relevant_uncertainty(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(
                MobileUsageBand::HIGH,
                '50.00',
                CommitmentStatus::UNKNOWN,
                null,
                false,
                PromotionStatus::NOT_ACTIVE,
            ),
            $this->catalogWith($this->mobileOffer('offer-trace-uncertain', 'Provider Trace', 'Mobile Trace', '30.00', MobileUsageBand::HIGH)),
        );

        $trace = $result->evaluationTrace();

        self::assertNotNull($trace);
        self::assertSame(EvaluationMode::EVALUATED_DEGRADED, $trace->evaluationMode());
        self::assertContains(DecisionDegradationCode::RELEVANT_UNCERTAINTY, $trace->decisionDegradationCodes());
        self::assertContains(AnalysisLimitationCode::MISSING_CRITICAL_DATA, $trace->analysisLimitations());
        self::assertNotNull($trace->selectedOfferVersionId());
        self::assertNotNull($trace->selectedFitLevel());
        self::assertNotNull($trace->selectedFriction());
    }

    public function test_it_does_not_assign_high_fit_to_asymmetric_offer_for_aggregated_usage(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForFiberMobile(MobileUsageBand::MEDIUM, FiberNeedBand::STANDARD, '70.00'),
            $this->catalogWith($this->fiberMobileOffer('offer-asymmetric', 'Provider Asym', 'Bundle Asym', '45.00', true)),
        );

        self::assertSame(Decision::SWITCH, $result->recommendation()->decision());
        self::assertSame(FitLevel::MEDIUM, $result->evaluationTrace()?->selectedFitLevel());
    }

    public function test_it_adds_multi_residence_limitation_when_supported(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00', multipleResidencesDetected: true),
            $this->catalogWith($this->mobileOffer('offer-multi', 'Provider Multi', 'Mobile Multi', '30.00', MobileUsageBand::HIGH)),
        );

        self::assertContains(AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED, $result->recommendation()->analysisLimitations());
        self::assertContains(AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED, $result->evaluationTrace()?->analysisLimitations());
    }

    public function test_it_builds_trace_with_evaluated_ranked_out_and_selected_offers(): void
    {
        $result = $this->evaluator()->evaluate(
            $this->snapshotForMobile(MobileUsageBand::HIGH, '50.00'),
            $this->catalogWith(
                $this->mobileOffer('offer-selected', 'Provider Selected', 'Mobile Selected', '30.00', MobileUsageBand::HIGH),
                $this->mobileOffer('offer-ranked-out', 'Provider Ranked', 'Mobile Ranked', '48.00', MobileUsageBand::HIGH),
                $this->mobileOffer('offer-hard-filtered', 'Provider Filtered', 'Mobile Filtered', '20.00', MobileUsageBand::MEDIUM),
            ),
        );

        $trace = $result->evaluationTrace();

        self::assertNotNull($trace);
        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $trace->evaluationMode());
        self::assertCount(3, $trace->evaluatedOfferVersionIds());
        self::assertCount(1, $trace->hardFilteredOffers());
        self::assertCount(1, $trace->rankedOutOffers());
        self::assertNotNull($trace->selectedOfferVersionId());
        self::assertSame(FitLevel::HIGH, $trace->selectedFitLevel());
    }

    private function evaluator(): AssessmentEvaluator
    {
        return new AssessmentEvaluator(
            new MinimumEvaluableInputRule(),
            new PublishedOfferEvaluationAssembler(),
            new FitCalculator(),
            new EstimatedImpactCalculator(),
            new ChangeFrictionCalculator(),
            new AlternativeSelector(),
            new SwitchRecommendationBuilder(),
            new WaitRecommendationBuilder(),
            new StayRecommendationBuilder(),
            new EvaluationTraceBuilder(),
        );
    }

    private function snapshotForMobileWithMissingUsage(): AssessmentInputSnapshot
    {
        $currentSituation = new CurrentSituation(
            'Provider A',
            ProductType::MOBILE,
            new Money('20.00', 'EUR'),
            1,
            null,
            null,
            CommitmentStatus::NO,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            false,
            false,
            DataProvenance::DECLARED_BY_USER,
        );

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );
    }

    private function snapshotForMobile(
        MobileUsageBand $mobileUsageBand,
        string $monthlyPrice,
        CommitmentStatus $commitmentStatus = CommitmentStatus::NO,
        ?ApproximateDate $commitmentEndApprox = null,
        bool $multipleResidencesDetected = false,
        PromotionStatus $promotionStatus = PromotionStatus::NOT_ACTIVE,
    ): AssessmentInputSnapshot {
        $currentSituation = new CurrentSituation(
            'Provider A',
            ProductType::MOBILE,
            new Money($monthlyPrice, 'EUR'),
            1,
            $mobileUsageBand,
            null,
            $commitmentStatus,
            $commitmentEndApprox,
            $promotionStatus,
            null,
            false,
            $multipleResidencesDetected,
            DataProvenance::DECLARED_BY_USER,
        );

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );
    }

    private function snapshotForFiberMobile(
        MobileUsageBand $mobileUsageBand,
        FiberNeedBand $fiberNeedBand,
        string $monthlyPrice,
    ): AssessmentInputSnapshot {
        $currentSituation = new CurrentSituation(
            'Provider A',
            ProductType::FIBER_MOBILE,
            new Money($monthlyPrice, 'EUR'),
            2,
            $mobileUsageBand,
            $fiberNeedBand,
            CommitmentStatus::NO,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            false,
            false,
            DataProvenance::DECLARED_BY_USER,
        );

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );
    }

    private function catalogWith(PublishedOfferVersionForEvaluation ...$offers): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catalog-test',
            'catalog-test-v1',
            $offers,
        );
    }

    private function mobileOffer(
        string $offerVersionId,
        string $provider,
        string $commercialName,
        string $monthlyPrice,
        MobileUsageBand $mobileUsageBandSupported,
    ): PublishedOfferVersionForEvaluation {
        return new PublishedOfferVersionForEvaluation(
            $offerVersionId,
            $provider,
            $commercialName,
            new Money($monthlyPrice, 'EUR'),
            1,
            false,
            null,
            '50 GB',
            ProductType::MOBILE,
            null,
            $mobileUsageBandSupported,
            false,
            false,
        );
    }

    private function fiberMobileOffer(
        string $offerVersionId,
        string $provider,
        string $commercialName,
        string $monthlyPrice,
        bool $asymmetricLines,
    ): PublishedOfferVersionForEvaluation {
        return new PublishedOfferVersionForEvaluation(
            $offerVersionId,
            $provider,
            $commercialName,
            new Money($monthlyPrice, 'EUR'),
            3,
            false,
            600,
            '50GB + 10GB + 10GB',
            ProductType::FIBER_MOBILE,
            FiberCapacityBand::HIGH,
            MobileUsageBand::HIGH,
            true,
            $asymmetricLines,
        );
    }
}
