<?php

declare(strict_types=1);

namespace App\Tests\Support\Advisor;

use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;

final class FakeAssessmentEvaluationPort implements AssessmentEvaluationPort
{
    private int $calls = 0;
    private ?AssessmentInputSnapshot $lastSnapshot = null;
    private ?PublishedCatalogForEvaluation $lastCatalog = null;

    public function __construct(private readonly AssessmentResult $result)
    {
    }

    public function evaluate(
        AssessmentInputSnapshot $snapshot,
        ?PublishedCatalogForEvaluation $catalog,
    ): AssessmentResult {
        $this->calls++;
        $this->lastSnapshot = $snapshot;
        $this->lastCatalog = $catalog;

        return $this->result;
    }

    public function calls(): int
    {
        return $this->calls;
    }

    public function wasCalled(): bool
    {
        return $this->calls > 0;
    }

    public function lastSnapshot(): ?AssessmentInputSnapshot
    {
        return $this->lastSnapshot;
    }

    public function lastCatalog(): ?PublishedCatalogForEvaluation
    {
        return $this->lastCatalog;
    }
}
