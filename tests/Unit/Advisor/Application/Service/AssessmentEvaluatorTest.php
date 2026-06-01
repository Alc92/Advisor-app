<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Service;

use App\Advisor\Application\Service\AssessmentEvaluator;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use App\Advisor\Domain\ValueObject\Money;
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

    private function evaluator(): AssessmentEvaluator
    {
        return new AssessmentEvaluator(
            new MinimumEvaluableInputRule(),
            new WaitRecommendationBuilder(),
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
}
