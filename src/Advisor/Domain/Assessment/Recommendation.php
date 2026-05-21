<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class Recommendation
{
    /**
     * @var list<string>
     */
    private array $tradeOffs;

    /**
     * @var list<string>
     */
    private array $risks;

    /**
     * @var list<AnalysisLimitationCode>
     */
    private array $analysisLimitations;

    private string $mainExplanation;
    private ?string $uncertaintySummary;

    /**
     * @param list<string> $tradeOffs
     * @param list<string> $risks
     * @param list<AnalysisLimitationCode> $analysisLimitations
     */
    public function __construct(
        private Decision $decision,
        private DecisionReasonCode $reasonCode,
        private ?WaitKind $waitKind,
        private ?Uuid $suggestedOfferVersionId,
        private ?RecommendedOfferSnapshot $suggestedOfferSnapshot,
        private EstimatedImpact $estimatedImpact,
        string $mainExplanation,
        array $tradeOffs,
        array $risks,
        ?string $uncertaintySummary,
        array $analysisLimitations,
        private ?ApproximateDate $recommendedReviewMoment,
        private ?ReviewTrigger $reviewTrigger,
    ) {
        $trimmedMain = trim($mainExplanation);

        if ($trimmedMain === '') {
            throw new InvalidArgumentException('Main explanation cannot be empty.');
        }

        $this->mainExplanation = $trimmedMain;

        $this->tradeOffs = $this->validateStringList($tradeOffs, 'tradeOffs');
        $this->risks = $this->validateStringList($risks, 'risks');
        $this->analysisLimitations = $this->validateLimitationList($analysisLimitations);

        if ($uncertaintySummary !== null) {
            $trimmedUncertainty = trim($uncertaintySummary);

            if ($trimmedUncertainty === '') {
                throw new InvalidArgumentException('Uncertainty summary cannot be empty.');
            }

            $this->uncertaintySummary = $trimmedUncertainty;
        } else {
            $this->uncertaintySummary = null;
        }

        if ($this->decision === Decision::SWITCH) {
            if ($this->suggestedOfferVersionId === null) {
                throw new InvalidArgumentException('SWITCH decision requires suggestedOfferVersionId.');
            }

            if ($this->suggestedOfferSnapshot === null) {
                throw new InvalidArgumentException('SWITCH decision requires suggestedOfferSnapshot.');
            }

            if ($this->waitKind !== null) {
                throw new InvalidArgumentException('SWITCH decision cannot have waitKind.');
            }

            if ($this->reviewTrigger !== null) {
                throw new InvalidArgumentException('SWITCH decision cannot have reviewTrigger.');
            }
        }

        if ($this->decision === Decision::WAIT) {
            if ($this->waitKind === null) {
                throw new InvalidArgumentException('WAIT decision requires waitKind.');
            }

            if ($this->reviewTrigger === null) {
                throw new InvalidArgumentException('WAIT decision requires reviewTrigger.');
            }

            if ($this->suggestedOfferVersionId !== null) {
                throw new InvalidArgumentException('WAIT decision cannot have suggestedOfferVersionId.');
            }

            if ($this->suggestedOfferSnapshot !== null) {
                throw new InvalidArgumentException('WAIT decision cannot have suggestedOfferSnapshot.');
            }
        }

        if ($this->decision === Decision::STAY) {
            if ($this->suggestedOfferVersionId !== null) {
                throw new InvalidArgumentException('STAY decision cannot have suggestedOfferVersionId.');
            }

            if ($this->suggestedOfferSnapshot !== null) {
                throw new InvalidArgumentException('STAY decision cannot have suggestedOfferSnapshot.');
            }

            if ($this->waitKind !== null) {
                throw new InvalidArgumentException('STAY decision cannot have waitKind.');
            }

            if ($this->reviewTrigger !== null) {
                throw new InvalidArgumentException('STAY decision cannot have reviewTrigger.');
            }
        }
    }

    public function decision(): Decision
    {
        return $this->decision;
    }

    public function reasonCode(): DecisionReasonCode
    {
        return $this->reasonCode;
    }

    public function waitKind(): ?WaitKind
    {
        return $this->waitKind;
    }

    public function suggestedOfferVersionId(): ?Uuid
    {
        return $this->suggestedOfferVersionId;
    }

    public function suggestedOfferSnapshot(): ?RecommendedOfferSnapshot
    {
        return $this->suggestedOfferSnapshot;
    }

    public function estimatedImpact(): EstimatedImpact
    {
        return $this->estimatedImpact;
    }

    public function mainExplanation(): string
    {
        return $this->mainExplanation;
    }

    /**
     * @return list<string>
     */
    public function tradeOffs(): array
    {
        return $this->tradeOffs;
    }

    /**
     * @return list<string>
     */
    public function risks(): array
    {
        return $this->risks;
    }

    public function uncertaintySummary(): ?string
    {
        return $this->uncertaintySummary;
    }

    /**
     * @return list<AnalysisLimitationCode>
     */
    public function analysisLimitations(): array
    {
        return $this->analysisLimitations;
    }

    public function recommendedReviewMoment(): ?ApproximateDate
    {
        return $this->recommendedReviewMoment;
    }

    public function reviewTrigger(): ?ReviewTrigger
    {
        return $this->reviewTrigger;
    }

    /**
     * @param list<string> $list
     * @return list<string>
     */
    private function validateStringList(array $list, string $name): array
    {
        $result = [];

        foreach ($list as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException("Each item in {$name} must be a string.");
            }

            $trimmed = trim($item);

            if ($trimmed === '') {
                throw new InvalidArgumentException("Items in {$name} cannot be empty.");
            }

            $result[] = $trimmed;
        }

        return array_values($result);
    }

    /**
     * @param list<AnalysisLimitationCode> $list
     * @return list<AnalysisLimitationCode>
     */
    private function validateLimitationList(array $list): array
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
}
