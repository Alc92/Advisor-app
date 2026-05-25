<?php

declare(strict_types=1);

namespace App\Advisor\Application\Port;

use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;

interface AssessmentEvaluationPort
{
    public function evaluate(
        AssessmentInputSnapshot $snapshot,
        ?PublishedCatalogForEvaluation $catalog,
    ): AssessmentResult;
}
