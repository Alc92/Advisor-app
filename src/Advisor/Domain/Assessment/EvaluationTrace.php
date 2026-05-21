<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\ChangeFriction;
use App\Advisor\Domain\Enum\DecisionDegradationCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FitLevel;
use App\Advisor\Domain\Enum\RuleCode;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class EvaluationTrace
{
    /**
     * @var list<Uuid>
     */
    private array $evaluatedOfferVersionIds;

    /**
     * @var list<HardFilteredOfferTrace>
     */
    private array $hardFilteredOffers;

    /**
     * @var list<RankedOutOfferTrace>
     */
    private array $rankedOutOffers;

    /**
     * @var list<RuleCode>
     */
    private array $appliedRuleCodes;

    /**
     * @var list<DecisionDegradationCode>
     */
    private array $decisionDegradationCodes;

    /**
     * @var list<AnalysisLimitationCode>
     */
    private array $analysisLimitations;

    /**
     * @param list<Uuid> $evaluatedOfferVersionIds
     * @param list<HardFilteredOfferTrace> $hardFilteredOffers
     * @param list<RankedOutOfferTrace> $rankedOutOffers
     * @param list<RuleCode> $appliedRuleCodes
     * @param list<DecisionDegradationCode> $decisionDegradationCodes
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function __construct(
        private EvaluationMode $evaluationMode,
        array $evaluatedOfferVersionIds,
        array $hardFilteredOffers,
        array $rankedOutOffers,
        array $appliedRuleCodes,
        array $decisionDegradationCodes,
        private ?Uuid $selectedOfferVersionId,
        private ?FitLevel $selectedFitLevel,
        private ?ChangeFriction $selectedFriction,
        array $analysisLimitations,
        private InputEvidenceSnapshot $inputEvidence,
    ) {
        $this->evaluatedOfferVersionIds = $this->validateUuidList($evaluatedOfferVersionIds);
        $this->hardFilteredOffers = $this->validateHardFilteredOffers($hardFilteredOffers);
        $this->rankedOutOffers = $this->validateRankedOutOffers($rankedOutOffers);
        $this->appliedRuleCodes = $this->validateRuleCodeList($appliedRuleCodes);
        $this->decisionDegradationCodes = $this->validateDegradationCodeList($decisionDegradationCodes);
        $this->analysisLimitations = $this->validateAnalysisLimitationList($analysisLimitations);

        $this->validateHardFilteredRankedOutOverlap();
        $this->validateNotEvaluatedMinimumNotMet();
        $this->validateSelectedOfferInEvaluated();
        $this->validateFitFrictionWithSelectedOffer();
    }

    public function evaluationMode(): EvaluationMode
    {
        return $this->evaluationMode;
    }

    /**
     * @return list<Uuid>
     */
    public function evaluatedOfferVersionIds(): array
    {
        return $this->evaluatedOfferVersionIds;
    }

    /**
     * @return list<HardFilteredOfferTrace>
     */
    public function hardFilteredOffers(): array
    {
        return $this->hardFilteredOffers;
    }

    /**
     * @return list<RankedOutOfferTrace>
     */
    public function rankedOutOffers(): array
    {
        return $this->rankedOutOffers;
    }

    /**
     * @return list<RuleCode>
     */
    public function appliedRuleCodes(): array
    {
        return $this->appliedRuleCodes;
    }

    /**
     * @return list<DecisionDegradationCode>
     */
    public function decisionDegradationCodes(): array
    {
        return $this->decisionDegradationCodes;
    }

    public function selectedOfferVersionId(): ?Uuid
    {
        return $this->selectedOfferVersionId;
    }

    public function selectedFitLevel(): ?FitLevel
    {
        return $this->selectedFitLevel;
    }

    public function selectedFriction(): ?ChangeFriction
    {
        return $this->selectedFriction;
    }

    /**
     * @return list<AnalysisLimitationCode>
     */
    public function analysisLimitations(): array
    {
        return $this->analysisLimitations;
    }

    public function inputEvidence(): InputEvidenceSnapshot
    {
        return $this->inputEvidence;
    }

    /**
     * @param list<Uuid> $list
     * @return list<Uuid>
     */
    private function validateUuidList(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof Uuid) {
                throw new InvalidArgumentException('Each evaluated offer version ID must be a Uuid.');
            }

            $key = $item->toRfc4122();

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate evaluated offer version ID.');
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param list<HardFilteredOfferTrace> $list
     * @return list<HardFilteredOfferTrace>
     */
    private function validateHardFilteredOffers(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof HardFilteredOfferTrace) {
                throw new InvalidArgumentException('Each hard filtered offer must be a HardFilteredOfferTrace.');
            }

            $key = $item->offerVersionId()->toRfc4122();

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate hard filtered offer version ID.');
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param list<RankedOutOfferTrace> $list
     * @return list<RankedOutOfferTrace>
     */
    private function validateRankedOutOffers(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof RankedOutOfferTrace) {
                throw new InvalidArgumentException('Each ranked out offer must be a RankedOutOfferTrace.');
            }

            $key = $item->offerVersionId()->toRfc4122();

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate ranked out offer version ID.');
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param list<RuleCode> $list
     * @return list<RuleCode>
     */
    private function validateRuleCodeList(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof RuleCode) {
                throw new InvalidArgumentException('Each applied rule code must be a RuleCode.');
            }

            $key = $item->value;

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate rule code: ' . $item->value);
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param list<DecisionDegradationCode> $list
     * @return list<DecisionDegradationCode>
     */
    private function validateDegradationCodeList(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof DecisionDegradationCode) {
                throw new InvalidArgumentException('Each degradation code must be a DecisionDegradationCode.');
            }

            $key = $item->value;

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate degradation code: ' . $item->value);
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param list<AnalysisLimitationCode> $list
     * @return list<AnalysisLimitationCode>
     */
    private function validateAnalysisLimitationList(array $list): array
    {
        $unique = [];

        foreach ($list as $item) {
            if (!$item instanceof AnalysisLimitationCode) {
                throw new InvalidArgumentException('Each analysis limitation must be an AnalysisLimitationCode.');
            }

            $key = $item->value;

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate analysis limitation: ' . $item->value);
            }

            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    private function validateHardFilteredRankedOutOverlap(): void
    {
        $hardFilteredIds = [];

        foreach ($this->hardFilteredOffers as $offer) {
            $hardFilteredIds[$offer->offerVersionId()->toRfc4122()] = true;
        }

        foreach ($this->rankedOutOffers as $offer) {
            $key = $offer->offerVersionId()->toRfc4122();

            if (isset($hardFilteredIds[$key])) {
                throw new InvalidArgumentException('Offer version ID cannot appear in both hardFilteredOffers and rankedOutOffers.');
            }
        }
    }

    private function validateNotEvaluatedMinimumNotMet(): void
    {
        if ($this->evaluationMode !== EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET) {
            return;
        }

        if (count($this->evaluatedOfferVersionIds) > 0) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have empty evaluatedOfferVersionIds.');
        }

        if (count($this->hardFilteredOffers) > 0) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have empty hardFilteredOffers.');
        }

        if (count($this->rankedOutOffers) > 0) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have empty rankedOutOffers.');
        }

        if ($this->selectedOfferVersionId !== null) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have null selectedOfferVersionId.');
        }

        if ($this->selectedFitLevel !== null) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have null selectedFitLevel.');
        }

        if ($this->selectedFriction !== null) {
            throw new InvalidArgumentException('NOT_EVALUATED_MINIMUM_NOT_MET must have null selectedFriction.');
        }
    }

    private function validateSelectedOfferInEvaluated(): void
    {
        if ($this->selectedOfferVersionId === null) {
            return;
        }

        $selectedId = $this->selectedOfferVersionId->toRfc4122();

        foreach ($this->evaluatedOfferVersionIds as $id) {
            if ($id->toRfc4122() === $selectedId) {
                return;
            }
        }

        throw new InvalidArgumentException('Selected offer version ID must appear in evaluatedOfferVersionIds.');
    }

    private function validateFitFrictionWithSelectedOffer(): void
    {
        if ($this->selectedOfferVersionId === null) {
            if ($this->selectedFitLevel !== null) {
                throw new InvalidArgumentException('selectedFitLevel must be null when selectedOfferVersionId is null.');
            }

            if ($this->selectedFriction !== null) {
                throw new InvalidArgumentException('selectedFriction must be null when selectedOfferVersionId is null.');
            }
        }
    }
}
