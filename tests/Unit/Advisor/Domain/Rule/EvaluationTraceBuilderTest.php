<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\HardFilteredOfferTrace;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Assessment\RankedOutOfferTrace;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\DiscardReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\RuleCode;
use App\Advisor\Domain\Rule\AlternativeEvaluation;
use App\Advisor\Domain\Rule\AlternativeSelectionResult;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\ValueObject\Money;
use App\Advisor\Domain\ValueObject\Percentage;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EvaluationTraceBuilderTest extends TestCase
{
    public function test_builds_complete_trace_for_normal_evaluation(): void
    {
        $builder = new EvaluationTraceBuilder();
        $offerVersionId = Uuid::v4();
        $hardFilteredId = Uuid::v4();
        $rankedOutId = Uuid::v4();

        $selectionResult = $this->buildSelectionResult(
            $offerVersionId,
            FitLevel::HIGH,
            ChangeFriction::LOW,
            [$hardFilteredId],
            [$rankedOutId],
        );

        $evaluatedIds = [$offerVersionId, $hardFilteredId, $rankedOutId];
        $appliedRules = [RuleCode::FIT_FILTER, RuleCode::SAVINGS_THRESHOLD];
        $inputEvidence = $this->buildInputEvidence();

        $trace = $builder->buildNormalTrace(
            $selectionResult,
            $evaluatedIds,
            $appliedRules,
            $inputEvidence,
        );

        self::assertSame(EvaluationMode::EVALUATED_NORMAL, $trace->evaluationMode());
        self::assertCount(3, $trace->evaluatedOfferVersionIds());
        self::assertCount(1, $trace->hardFilteredOffers());
        self::assertSame($hardFilteredId->toRfc4122(), $trace->hardFilteredOffers()[0]->offerVersionId()->toRfc4122());
        self::assertCount(1, $trace->rankedOutOffers());
        self::assertSame($rankedOutId->toRfc4122(), $trace->rankedOutOffers()[0]->offerVersionId()->toRfc4122());
        self::assertSame($appliedRules, $trace->appliedRuleCodes());
        self::assertSame([], $trace->decisionDegradationCodes());
        self::assertSame([], $trace->analysisLimitations());
        self::assertSame($inputEvidence, $trace->inputEvidence());
    }

    public function test_builds_degraded_trace(): void
    {
        $builder = new EvaluationTraceBuilder();
        $offerVersionId = Uuid::v4();
        $selectionResult = $this->buildSelectionResult(
            $offerVersionId,
            FitLevel::MEDIUM,
            ChangeFriction::HIGH,
            [],
            [],
        );

        $degradations = [DecisionDegradationCode::HIGH_FRICTION];
        $analysisLimitations = [AnalysisLimitationCode::SIMPLIFIED_MODEL_LIMITATION];
        $inputEvidence = $this->buildInputEvidence();

        $trace = $builder->buildDegradedTrace(
            $selectionResult,
            [$offerVersionId],
            [RuleCode::FIT_FILTER, RuleCode::FRICTION_CHECK],
            $degradations,
            $analysisLimitations,
            $inputEvidence,
        );

        self::assertSame(EvaluationMode::EVALUATED_DEGRADED, $trace->evaluationMode());
        self::assertSame($degradations, $trace->decisionDegradationCodes());
        self::assertSame($analysisLimitations, $trace->analysisLimitations());
        self::assertCount(1, $trace->evaluatedOfferVersionIds());
        self::assertCount(0, $trace->hardFilteredOffers());
        self::assertCount(0, $trace->rankedOutOffers());
    }

    public function test_builds_trace_without_catalog_when_minimum_input_is_not_met(): void
    {
        $builder = new EvaluationTraceBuilder();
        $appliedRules = [RuleCode::MINIMUM_DATA_CHECK];
        $analysisLimitations = [AnalysisLimitationCode::MISSING_CRITICAL_DATA];
        $inputEvidence = $this->buildInputEvidence();

        $trace = $builder->buildWithoutCatalogTrace(
            $appliedRules,
            $analysisLimitations,
            $inputEvidence,
        );

        self::assertSame(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET, $trace->evaluationMode());
        self::assertSame([], $trace->evaluatedOfferVersionIds());
        self::assertSame([], $trace->hardFilteredOffers());
        self::assertSame([], $trace->rankedOutOffers());
        self::assertSame($appliedRules, $trace->appliedRuleCodes());
        self::assertSame($analysisLimitations, $trace->analysisLimitations());
        self::assertSame($inputEvidence, $trace->inputEvidence());
    }

    public function test_builds_trace_with_multi_residence_limitation(): void
    {
        $builder = new EvaluationTraceBuilder();
        $offerVersionId = Uuid::v4();
        $selectionResult = $this->buildSelectionResult(
            $offerVersionId,
            FitLevel::HIGH,
            ChangeFriction::LOW,
            [],
            [],
        );

        $analysisLimitations = [AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED];
        $inputEvidence = $this->buildInputEvidence();

        $trace = $builder->buildDegradedTrace(
            $selectionResult,
            [$offerVersionId],
            [RuleCode::FIT_FILTER],
            [],
            $analysisLimitations,
            $inputEvidence,
        );

        self::assertSame(EvaluationMode::EVALUATED_DEGRADED, $trace->evaluationMode());
        self::assertContains(AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED, $trace->analysisLimitations());
    }

    public function test_rejects_duplicated_offer_between_hard_filtered_and_ranked_out(): void
    {
        $builder = new EvaluationTraceBuilder();
        $offerVersionId = Uuid::v4();
        $duplicatedId = Uuid::v4();

        $hardFiltered = [new HardFilteredOfferTrace($duplicatedId, [DiscardReasonCode::INSUFFICIENT_FIT])];
        $rankedOut = [new RankedOutOfferTrace($duplicatedId, [DiscardReasonCode::INSUFFICIENT_IMPROVEMENT])];

        $traceOfferVersionId = TelecomOfferVersionId::fromUuid($offerVersionId);
        $selected = new AlternativeEvaluation(
            $traceOfferVersionId,
            FitLevel::HIGH,
            new EstimatedImpact(new Money('10', 'EUR'), new Percentage('5'), ImpactType::MONTHLY_SAVINGS, 'Ahorro claro.'),
            ChangeFriction::LOW,
            false,
        );

        $selectionResult = new AlternativeSelectionResult($selected, $hardFiltered, $rankedOut);

        $this->expectException(InvalidArgumentException::class);

        $builder->buildNormalTrace(
            $selectionResult,
            [$offerVersionId, $duplicatedId],
            [RuleCode::FIT_FILTER],
            $this->buildInputEvidence(),
        );
    }

    private function buildSelectionResult(
        Uuid $selectedId,
        FitLevel $fitLevel,
        ChangeFriction $changeFriction,
        array $hardFilteredIds,
        array $rankedOutIds,
    ): AlternativeSelectionResult {
        $selected = new AlternativeEvaluation(
            TelecomOfferVersionId::fromUuid($selectedId),
            $fitLevel,
            new EstimatedImpact(new Money('10', 'EUR'), new Percentage('5'), ImpactType::MONTHLY_SAVINGS, 'Ahorro claro.'),
            $changeFriction,
            false,
        );

        $hardFiltered = array_map(
            fn (Uuid $id) => new HardFilteredOfferTrace($id, [DiscardReasonCode::INSUFFICIENT_FIT]),
            $hardFilteredIds,
        );

        $rankedOut = array_map(
            fn (Uuid $id) => new RankedOutOfferTrace($id, [DiscardReasonCode::INSUFFICIENT_IMPROVEMENT]),
            $rankedOutIds,
        );

        return new AlternativeSelectionResult($selected, $hardFiltered, $rankedOut);
    }

    private function buildInputEvidence(): InputEvidenceSnapshot
    {
        return new InputEvidenceSnapshot(
            productType: \App\Advisor\Domain\Enum\ProductType::FIBER,
            approxMonthlyPrice: new Money('50', 'EUR'),
            mobileUsageBand: null,
            fiberNeedBand: null,
            commitmentStatus: \App\Advisor\Domain\Enum\CommitmentStatus::NO,
            promotionStatus: \App\Advisor\Domain\Enum\PromotionStatus::NOT_ACTIVE,
            userPreference: \App\Advisor\Domain\Enum\UserPreference::BALANCE,
            additionalConditionProfileUsed: false,
        );
    }
}
