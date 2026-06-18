<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum EvaluationMode: string
{
    case NOT_EVALUATED_MINIMUM_NOT_MET = 'NOT_EVALUATED_MINIMUM_NOT_MET';
    case EVALUATED_NORMAL = 'EVALUATED_NORMAL';
    case EVALUATED_DEGRADED = 'EVALUATED_DEGRADED';
}
