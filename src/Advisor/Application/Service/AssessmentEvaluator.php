<?php

declare(strict_types=1);

namespace App\Advisor\Application\Service;

use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\RuleCode;
use App\Advisor\Domain\Rule\AlternativeEvaluation;
use App\Advisor\Domain\Rule\AlternativeSelectionResult;
use App\Advisor\Domain\Rule\AlternativeSelector;
use App\Advisor\Domain\Rule\ChangeFrictionCalculator;
use App\Advisor\Domain\Rule\EstimatedImpactCalculator;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\Rule\FitCalculator;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\Rule\StayRecommendationBuilder;
use App\Advisor\Domain\Rule\SwitchRecommendationBuilder;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use App\Catalog\Domain\TelecomOfferNormalizedData;
use DateTimeImmutable;
use LogicException;

final readonly class AssessmentEvaluator implements AssessmentEvaluationPort
{
    public function __construct(
        private MinimumEvaluableInputRule $minimumEvaluableInputRule,
        private PublishedOfferEvaluationAssembler $publishedOfferEvaluationAssembler,
        private FitCalculator $fitCalculator,
        private EstimatedImpactCalculator $estimatedImpactCalculator,
        private ChangeFrictionCalculator $changeFrictionCalculator,
        private AlternativeSelector $alternativeSelector,
        private SwitchRecommendationBuilder $switchRecommendationBuilder,
        private WaitRecommendationBuilder $waitRecommendationBuilder,
        private StayRecommendationBuilder $stayRecommendationBuilder,
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

        if ($catalog === null) {
            throw new LogicException('Published catalog is required when minimum input is met.');
        }

        return $this->evaluateCatalog($snapshot, $catalog, $generatedAt);
    }

    private function evaluateCatalog(
        AssessmentInputSnapshot $snapshot,
        PublishedCatalogForEvaluation $catalog,
        DateTimeImmutable $generatedAt,
    ): AssessmentResult {
        $currentSituation = $snapshot->currentSituation();
        $analysisLimitations = $this->analysisLimitations($snapshot);
        $alternatives = [];
        $offersByInternalId = [];
        $evaluatedOfferVersionIds = [];
        $changeFriction = $this->changeFrictionCalculator->calculate($currentSituation, $snapshot->inputQuality());

        foreach ($catalog->offerVersions() as $publishedOffer) {
            $offer = $this->publishedOfferEvaluationAssembler->assemble($publishedOffer);
            $fit = $this->fitCalculator->calculate(
                $currentSituation,
                $snapshot->userPreference(),
                $snapshot->additionalConditionProfile(),
                $this->normalizedDataFrom($offer),
            );
            $impact = $this->estimatedImpactCalculator->calculate(
                $currentSituation->approxMonthlyPrice(),
                $offer->monthlyPrice(),
            );
            $alternative = new AlternativeEvaluation(
                $offer->stableInternalOfferVersionId(),
                $fit,
                $impact,
                $changeFriction,
                false,
            );

            $alternatives[] = $alternative;
            $offersByInternalId[$offer->stableInternalOfferVersionId()->toString()] = $offer;
            $evaluatedOfferVersionIds[] = $offer->stableInternalOfferVersionId()->value();
        }

        $selectionResult = $this->alternativeSelector->select($alternatives);
        $inputEvidence = $this->buildInputEvidenceSnapshot($snapshot);
        $appliedRules = [
            RuleCode::MINIMUM_DATA_CHECK,
            RuleCode::FIT_FILTER,
            RuleCode::SAVINGS_THRESHOLD,
            RuleCode::FRICTION_CHECK,
            RuleCode::FINAL_SELECTION,
        ];

        if ($currentSituation->commitmentStatus() === CommitmentStatus::YES) {
            $recommendation = $this->waitRecommendationBuilder->buildForActiveCommitment(
                $this->commitmentEndDateOr($snapshot, $generatedAt),
                'Conviene esperar al fin de la permanencia antes de cambiar.',
            );
            $recommendation = $this->withAnalysisLimitations($recommendation, $analysisLimitations);
            $trace = $this->evaluationTraceBuilder->buildDegradedTrace(
                $selectionResult,
                $evaluatedOfferVersionIds,
                $appliedRules,
                [DecisionDegradationCode::HIGH_FRICTION, DecisionDegradationCode::TIMING_NOT_OPTIMAL],
                $analysisLimitations,
                $inputEvidence,
            );

            return new AssessmentResult($recommendation, $trace, $generatedAt);
        }

        $selectedAlternative = $selectionResult->selectedAlternative();

        if ($selectedAlternative !== null) {
            $selectedOffer = $offersByInternalId[$selectedAlternative->offerVersionId()->toString()]
                ?? throw new LogicException('Selected offer was not found in evaluated offers.');
            $recommendation = $this->switchRecommendationBuilder->buildSwitchFromSnapshot(
                $selectedAlternative,
                $this->publishedOfferEvaluationAssembler->createRecommendedOfferSnapshot($selectedOffer),
                $analysisLimitations,
            );
            $trace = $this->buildCatalogTrace(
                $selectionResult,
                $evaluatedOfferVersionIds,
                $appliedRules,
                $analysisLimitations,
                $inputEvidence,
            );

            return new AssessmentResult($recommendation, $trace, $generatedAt);
        }

        $recommendation = $this->stayRecommendationBuilder->buildForInsufficientImprovement(
            $this->bestInsufficientImpact($alternatives),
            'No hay una mejora suficiente para recomendar un cambio ahora.',
        );
        $recommendation = $this->withAnalysisLimitations($recommendation, $analysisLimitations);
        $trace = $this->buildCatalogTrace(
            $selectionResult,
            $evaluatedOfferVersionIds,
            $appliedRules,
            $analysisLimitations,
            $inputEvidence,
        );

        return new AssessmentResult($recommendation, $trace, $generatedAt);
    }

    private function normalizedDataFrom(EvaluablePublishedOffer $offer): TelecomOfferNormalizedData
    {
        return new TelecomOfferNormalizedData(
            $offer->fiberCapacityBand(),
            $offer->mobileUsageBandSupported(),
            $offer->mobileLinesIncluded(),
            $offer->fiberIncluded(),
            $offer->tvIncluded(),
            $offer->asymmetricLines(),
        );
    }

    /**
     * @return list<AnalysisLimitationCode>
     */
    private function analysisLimitations(AssessmentInputSnapshot $snapshot): array
    {
        if ($snapshot->currentSituation()->multipleResidencesDetected()) {
            return [AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED];
        }

        return [];
    }

    private function commitmentEndDateOr(AssessmentInputSnapshot $snapshot, DateTimeImmutable $fallback): DateTimeImmutable
    {
        $commitmentEnd = $snapshot->currentSituation()->commitmentEndApprox();

        if ($commitmentEnd === null) {
            return $fallback;
        }

        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $commitmentEnd->year(), $commitmentEnd->month()));
    }

    /**
     * @param list<AlternativeEvaluation> $alternatives
     */
    private function bestInsufficientImpact(array $alternatives): EstimatedImpact
    {
        foreach ($alternatives as $alternative) {
            if ($alternative->estimatedImpact()->impactType() === ImpactType::NO_CLEAR_IMPACT) {
                return $alternative->estimatedImpact();
            }
        }

        return $this->stayRecommendationBuilder->buildForAlreadyOptimized(
            'No hay una mejora suficiente para recomendar un cambio ahora.',
        )->estimatedImpact();
    }

    /**
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    private function withAnalysisLimitations(Recommendation $recommendation, array $analysisLimitations): Recommendation
    {
        if ($analysisLimitations === []) {
            return $recommendation;
        }

        return new Recommendation(
            $recommendation->decision(),
            $recommendation->reasonCode(),
            $recommendation->waitKind(),
            $recommendation->suggestedOfferVersionId(),
            $recommendation->suggestedOfferSnapshot(),
            $recommendation->estimatedImpact(),
            $recommendation->mainExplanation(),
            $recommendation->tradeOffs(),
            $recommendation->risks(),
            $recommendation->uncertaintySummary(),
            $analysisLimitations,
            $recommendation->recommendedReviewMoment(),
            $recommendation->reviewTrigger(),
        );
    }

    /**
     * @param list<\Symfony\Component\Uid\Uuid> $evaluatedOfferVersionIds
     * @param list<RuleCode> $appliedRules
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    private function buildCatalogTrace(
        AlternativeSelectionResult $selectionResult,
        array $evaluatedOfferVersionIds,
        array $appliedRules,
        array $analysisLimitations,
        InputEvidenceSnapshot $inputEvidence,
    ): \App\Advisor\Domain\Assessment\EvaluationTrace {
        if ($analysisLimitations !== []) {
            return $this->evaluationTraceBuilder->buildDegradedTrace(
                $selectionResult,
                $evaluatedOfferVersionIds,
                $appliedRules,
                [],
                $analysisLimitations,
                $inputEvidence,
            );
        }

        return $this->evaluationTraceBuilder->buildNormalTrace(
            $selectionResult,
            $evaluatedOfferVersionIds,
            $appliedRules,
            $inputEvidence,
        );
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
