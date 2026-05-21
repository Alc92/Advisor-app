<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Enum\EvaluationMode;

final readonly class MinimumEvaluableInputRuleResult
{
    private function __construct(
        private EvaluationMode $evaluationMode,
        private ?AssessmentResult $assessmentResult,
    ) {
    }

    public static function evaluable(): self
    {
        return new self(
            EvaluationMode::EVALUATED_NORMAL,
            null,
        );
    }

    public static function minimumNotMet(AssessmentResult $assessmentResult): self
    {
        return new self(
            EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET,
            $assessmentResult,
        );
    }

    public function evaluationMode(): EvaluationMode
    {
        return $this->evaluationMode;
    }

    public function assessmentResult(): ?AssessmentResult
    {
        return $this->assessmentResult;
    }

    public function allowsCatalogEvaluation(): bool
    {
        return $this->evaluationMode !== EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET;
    }
}
