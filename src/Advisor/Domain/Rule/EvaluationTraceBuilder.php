<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EvaluationTrace;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\RuleCode;
use Symfony\Component\Uid\Uuid;

final class EvaluationTraceBuilder
{
    /**
     * @param list<Uuid> $evaluatedOfferVersionIds
     * @param list<RuleCode> $appliedRules
     */
    public function buildNormalTrace(
        AlternativeSelectionResult $selectionResult,
        array $evaluatedOfferVersionIds,
        array $appliedRules,
        InputEvidenceSnapshot $inputEvidence,
    ): EvaluationTrace {
        $selected = $selectionResult->selectedAlternative();

        return new EvaluationTrace(
            evaluationMode: EvaluationMode::EVALUATED_NORMAL,
            evaluatedOfferVersionIds: $evaluatedOfferVersionIds,
            hardFilteredOffers: $selectionResult->hardFilteredOffers(),
            rankedOutOffers: $selectionResult->rankedOutOffers(),
            appliedRuleCodes: $appliedRules,
            decisionDegradationCodes: [],
            selectedOfferVersionId: $selected?->offerVersionId()->value(),
            selectedFitLevel: $selected?->fitLevel(),
            selectedFriction: $selected?->changeFriction(),
            analysisLimitations: [],
            inputEvidence: $inputEvidence,
        );
    }

    /**
     * @param list<Uuid> $evaluatedOfferVersionIds
     * @param list<RuleCode> $appliedRules
     * @param list<DecisionDegradationCode> $degradations
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function buildDegradedTrace(
        AlternativeSelectionResult $selectionResult,
        array $evaluatedOfferVersionIds,
        array $appliedRules,
        array $degradations,
        array $analysisLimitations,
        InputEvidenceSnapshot $inputEvidence,
    ): EvaluationTrace {
        $selected = $selectionResult->selectedAlternative();

        return new EvaluationTrace(
            evaluationMode: EvaluationMode::EVALUATED_DEGRADED,
            evaluatedOfferVersionIds: $evaluatedOfferVersionIds,
            hardFilteredOffers: $selectionResult->hardFilteredOffers(),
            rankedOutOffers: $selectionResult->rankedOutOffers(),
            appliedRuleCodes: $appliedRules,
            decisionDegradationCodes: $degradations,
            selectedOfferVersionId: $selected?->offerVersionId()->value(),
            selectedFitLevel: $selected?->fitLevel(),
            selectedFriction: $selected?->changeFriction(),
            analysisLimitations: $analysisLimitations,
            inputEvidence: $inputEvidence,
        );
    }

    /**
     * @param list<RuleCode> $appliedRules
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function buildWithoutCatalogTrace(
        array $appliedRules,
        array $analysisLimitations,
        InputEvidenceSnapshot $inputEvidence,
    ): EvaluationTrace {
        return new EvaluationTrace(
            evaluationMode: EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET,
            evaluatedOfferVersionIds: [],
            hardFilteredOffers: [],
            rankedOutOffers: [],
            appliedRuleCodes: $appliedRules,
            decisionDegradationCodes: [],
            selectedOfferVersionId: null,
            selectedFitLevel: null,
            selectedFriction: null,
            analysisLimitations: $analysisLimitations,
            inputEvidence: $inputEvidence,
        );
    }
}
