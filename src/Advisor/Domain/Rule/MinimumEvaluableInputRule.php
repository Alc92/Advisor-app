<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\EvaluationTrace;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\RuleCode;
use App\Advisor\Domain\Enum\WaitKind;
use DateTimeImmutable;

final class MinimumEvaluableInputRule
{
    public function evaluate(
        AssessmentInputSnapshot $inputSnapshot,
        DateTimeImmutable $generatedAt,
    ): MinimumEvaluableInputRuleResult {
        if ($inputSnapshot->minimumDataForEvaluationIsMet()) {
            return MinimumEvaluableInputRuleResult::evaluable();
        }

        return MinimumEvaluableInputRuleResult::minimumNotMet(
            $this->buildMinimumNotMetResult($inputSnapshot, $generatedAt),
        );
    }

    private function buildMinimumNotMetResult(
        AssessmentInputSnapshot $inputSnapshot,
        DateTimeImmutable $generatedAt,
    ): AssessmentResult {
        $currentSituation = $inputSnapshot->currentSituation();

        $inputEvidence = new InputEvidenceSnapshot(
            $currentSituation->productType(),
            $currentSituation->approxMonthlyPrice(),
            $currentSituation->mobileUsageBand(),
            $currentSituation->fiberNeedBand(),
            $currentSituation->commitmentStatus(),
            $currentSituation->promotionStatus(),
            $inputSnapshot->userPreference(),
            $inputSnapshot->hasAdditionalConditionProfile(),
        );

        $estimatedImpact = new EstimatedImpact(
            null,
            null,
            ImpactType::NO_CLEAR_IMPACT,
            'No se estima impacto económico porque faltan datos mínimos.',
        );

        $recommendation = new Recommendation(
            Decision::WAIT,
            DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
            WaitKind::UNCERTAINTY_OR_MISSING_INFO,
            null,
            null,
            $estimatedImpact,
            'Necesitamos más información para comparar ofertas con suficiente fiabilidad.',
            [],
            [],
            'Faltan datos mínimos de uso para evaluar alternativas.',
            [AnalysisLimitationCode::MISSING_CRITICAL_DATA],
            null,
            ReviewTrigger::CHECK_MISSING_INFORMATION,
        );

        $trace = new EvaluationTrace(
            EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET,
            [],
            [],
            [],
            [RuleCode::MINIMUM_DATA_CHECK],
            [],
            null,
            null,
            null,
            [AnalysisLimitationCode::MISSING_CRITICAL_DATA],
            $inputEvidence,
        );

        return new AssessmentResult(
            $recommendation,
            $trace,
            $generatedAt,
        );
    }
}
