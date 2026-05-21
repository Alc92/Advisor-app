<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\RuleCode;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MinimumEvaluableInputRuleTest extends TestCase
{
    private function buildInputSnapshot(
        ProductType $productType,
        ?MobileUsageBand $mobileUsageBand,
        ?FiberNeedBand $fiberNeedBand,
    ): AssessmentInputSnapshot {
        $currentSituation = new CurrentSituation(
            'Test Provider',
            $productType,
            new Money('20.00', 'EUR'),
            $productType === ProductType::FIBER ? null : 1,
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

        $inputQuality = InputQuality::fromCurrentSituation($currentSituation);

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            $inputQuality,
        );
    }

    public function test_evaluable_input_allows_catalog_evaluation(): void
    {
        $snapshot = $this->buildInputSnapshot(
            ProductType::MOBILE,
            MobileUsageBand::MEDIUM,
            null,
        );

        $rule = new MinimumEvaluableInputRule();
        $result = $rule->evaluate($snapshot, new DateTimeImmutable('2026-01-01 10:00:00'));

        self::assertTrue($result->allowsCatalogEvaluation());
        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $result->evaluationMode());
        self::assertNull($result->assessmentResult());
    }

    public function test_missing_mobile_usage_returns_minimum_not_met_wait_result(): void
    {
        $snapshot = $this->buildInputSnapshot(
            ProductType::MOBILE,
            null,
            null,
        );

        $rule = new MinimumEvaluableInputRule();
        $result = $rule->evaluate($snapshot, new DateTimeImmutable('2026-01-01 10:00:00'));

        self::assertFalse($result->allowsCatalogEvaluation());
        self::assertSame(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET, $result->evaluationMode());
        self::assertNotNull($result->assessmentResult());

        $recommendation = $result->assessmentResult()->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
    }

    public function test_missing_fiber_usage_returns_minimum_not_met_wait_result(): void
    {
        $snapshot = $this->buildInputSnapshot(
            ProductType::FIBER,
            null,
            null,
        );

        $rule = new MinimumEvaluableInputRule();
        $result = $rule->evaluate($snapshot, new DateTimeImmutable('2026-01-01 10:00:00'));

        self::assertFalse($result->allowsCatalogEvaluation());
        self::assertSame(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET, $result->evaluationMode());
        self::assertNotNull($result->assessmentResult());

        $recommendation = $result->assessmentResult()->recommendation();

        self::assertSame(Decision::WAIT, $recommendation->decision());
        self::assertSame(DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY, $recommendation->reasonCode());
        self::assertSame(WaitKind::UNCERTAINTY_OR_MISSING_INFO, $recommendation->waitKind());
        self::assertSame(ReviewTrigger::CHECK_MISSING_INFORMATION, $recommendation->reviewTrigger());
    }

    public function test_fiber_mobile_with_both_usages_allows_catalog_evaluation(): void
    {
        $snapshot = $this->buildInputSnapshot(
            ProductType::FIBER_MOBILE,
            MobileUsageBand::HIGH,
            FiberNeedBand::STANDARD,
        );

        $rule = new MinimumEvaluableInputRule();
        $result = $rule->evaluate($snapshot, new DateTimeImmutable('2026-01-01 10:00:00'));

        self::assertTrue($result->allowsCatalogEvaluation());
        self::assertNull($result->assessmentResult());
    }

    public function test_minimum_not_met_result_contains_minimum_data_check_trace(): void
    {
        $snapshot = $this->buildInputSnapshot(
            ProductType::MOBILE,
            null,
            null,
        );

        $rule = new MinimumEvaluableInputRule();
        $result = $rule->evaluate($snapshot, new DateTimeImmutable('2026-01-01 10:00:00'));

        $assessmentResult = $result->assessmentResult();
        self::assertNotNull($assessmentResult);

        $trace = $assessmentResult->evaluationTrace();
        self::assertNotNull($trace);

        self::assertSame(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET, $trace->evaluationMode());
        self::assertSame([], $trace->evaluatedOfferVersionIds());
        self::assertSame([], $trace->hardFilteredOffers());
        self::assertSame([], $trace->rankedOutOffers());
        self::assertSame([RuleCode::MINIMUM_DATA_CHECK], array_map(fn ($c) => $c, $trace->appliedRuleCodes()));
        self::assertSame([AnalysisLimitationCode::MISSING_CRITICAL_DATA], array_map(fn ($c) => $c, $trace->analysisLimitations()));

        $inputEvidence = $trace->inputEvidence();

        self::assertSame(ProductType::MOBILE, $inputEvidence->productType());
        self::assertSame('20.00', $inputEvidence->approxMonthlyPrice()->amount());
        self::assertNull($inputEvidence->mobileUsageBand());
        self::assertNull($inputEvidence->fiberNeedBand());
        self::assertSame(CommitmentStatus::NO, $inputEvidence->commitmentStatus());
        self::assertSame(PromotionStatus::NOT_ACTIVE, $inputEvidence->promotionStatus());
        self::assertSame(UserPreference::BALANCE, $inputEvidence->userPreference());
        self::assertFalse($inputEvidence->additionalConditionProfileUsed());
    }
}
