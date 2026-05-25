<?php

declare(strict_types=1);

namespace App\Advisor\Application\Port;

use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\ValueObject\AssessmentId;

interface AssessmentRepository
{
    public function save(Assessment $assessment): void;

    public function get(AssessmentId $id): ?Assessment;
}
