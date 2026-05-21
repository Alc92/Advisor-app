<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum DecisionDegradationCode: string
{
    case HIGH_FRICTION = 'HIGH_FRICTION';
    case RELEVANT_UNCERTAINTY = 'RELEVANT_UNCERTAINTY';
    case TIMING_NOT_OPTIMAL = 'TIMING_NOT_OPTIMAL';
    case MISSING_CRITICAL_DATA = 'MISSING_CRITICAL_DATA';
}
