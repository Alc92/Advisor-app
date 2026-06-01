<?php

declare(strict_types=1);

namespace App\Advisor\Application\Service;

use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\RuleCode;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use DateTimeImmutable;
use LogicException;

final readonly class AssessmentEvaluator implements AssessmentEvaluationPort
{
    public function __construct(
        private MinimumEvaluableInputRule $minimumEvaluableInputRule,
        private WaitRecommendationBuilder $waitRecommendationBuilder,
        private EvaluationTraceBuilder $evaluationTraceBuilder,
    ) {
    }

    public function evaluate(
        AssessmentInputSnapshot $snapshot,
        ?PublishedCatalogForEvaluation $catalog,
    ): AssessmentResult {
        $generatedAt = new DateTimeImmutable();
        $minimumResult = $this->minimumEvaluableInputRule->evaluate($snapshot, $generatedAt);

        if (!$minimumResult->allowsCatalogEvaluation()) {
            $analysisLimitations = [AnalysisLimitationCode::MISSING_CRITICAL_DATA];

            $recommendation = $this->waitRecommendationBuilder->buildForMinimumInputNotMet(
                'Necesitamos más información para comparar ofertas con suficiente fiabilidad.',
                $analysisLimitations,
            );

            $trace = $this->evaluationTraceBuilder->buildWithoutCatalogTrace(
                [RuleCode::MINIMUM_DATA_CHECK],
                $analysisLimitations,
                $this->buildInputEvidenceSnapshot($snapshot),
            );

            return new AssessmentResult(
                $recommendation,
                $trace,
                $generatedAt,
            );
        }

        throw new LogicException('Evaluation with sufficient minimum input is intentionally not implemented in MVP7.2.');
    }

    private function buildInputEvidenceSnapshot(AssessmentInputSnapshot $snapshot): InputEvidenceSnapshot
    {
        $currentSituation = $snapshot->currentSituation();

        return new InputEvidenceSnapshot(
            $currentSituation->productType(),
            $currentSituation->approxMonthlyPrice(),
            $currentSituation->mobileUsageBand(),
            $currentSituation->fiberNeedBand(),
            $currentSituation->commitmentStatus(),
            $currentSituation->promotionStatus(),
            $snapshot->userPreference(),
            $snapshot->hasAdditionalConditionProfile(),
        );
    }
}
