<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\EvaluationTrace;
use App\Advisor\Domain\Assessment\HardFilteredOfferTrace;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Assessment\RankedOutOfferTrace;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\RuleCode;
use InvalidArgumentException;
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

        $this->validateTraceConsistency(
            $evaluatedOfferVersionIds,
            $selectionResult->hardFilteredOffers(),
            $selectionResult->rankedOutOffers(),
            $selected,
        );

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

        $this->validateTraceConsistency(
            $evaluatedOfferVersionIds,
            $selectionResult->hardFilteredOffers(),
            $selectionResult->rankedOutOffers(),
            $selected,
        );

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
     * @param list<Uuid> $evaluatedOfferVersionIds
     * @param list<HardFilteredOfferTrace> $hardFilteredOffers
     * @param list<RankedOutOfferTrace> $rankedOutOffers
     */
    private function validateTraceConsistency(
        array $evaluatedOfferVersionIds,
        array $hardFilteredOffers,
        array $rankedOutOffers,
        ?AlternativeEvaluation $selected,
    ): void {
        $evaluatedIds = [];

        foreach ($evaluatedOfferVersionIds as $id) {
            $evaluatedIds[$id->toRfc4122()] = true;
        }

        $hardFilteredIds = [];

        foreach ($hardFilteredOffers as $offer) {
            $offerId = $offer->offerVersionId()->toRfc4122();

            if (!isset($evaluatedIds[$offerId])) {
                throw new InvalidArgumentException('Hard filtered offer version ID must appear in evaluatedOfferVersionIds.');
            }

            $hardFilteredIds[$offerId] = true;
        }

        $rankedOutIds = [];

        foreach ($rankedOutOffers as $offer) {
            $offerId = $offer->offerVersionId()->toRfc4122();

            if (!isset($evaluatedIds[$offerId])) {
                throw new InvalidArgumentException('Ranked out offer version ID must appear in evaluatedOfferVersionIds.');
            }

            if (isset($hardFilteredIds[$offerId])) {
                throw new InvalidArgumentException('Offer version ID cannot appear in both hardFilteredOffers and rankedOutOffers.');
            }

            $rankedOutIds[$offerId] = true;
        }

        if ($selected === null) {
            return;
        }

        $selectedId = $selected->offerVersionId()->value()->toRfc4122();

        if (isset($hardFilteredIds[$selectedId])) {
            throw new InvalidArgumentException('Selected offer version ID cannot appear in hardFilteredOffers.');
        }

        if (isset($rankedOutIds[$selectedId])) {
            throw new InvalidArgumentException('Selected offer version ID cannot appear in rankedOutOffers.');
        }
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
