<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use DateTimeImmutable;

final readonly class AssessmentResult
{
    public function __construct(
        private Recommendation $recommendation,
        private ?EvaluationTrace $evaluationTrace,
        private DateTimeImmutable $generatedAt,
    ) {
    }

    public function recommendation(): Recommendation
    {
        return $this->recommendation;
    }

    public function evaluationTrace(): ?EvaluationTrace
    {
        return $this->evaluationTrace;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }
}
