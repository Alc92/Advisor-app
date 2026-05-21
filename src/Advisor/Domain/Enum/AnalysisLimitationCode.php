<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum AnalysisLimitationCode: string
{
    case MULTI_RESIDENCE_NOT_SUPPORTED = 'MULTI_RESIDENCE_NOT_SUPPORTED';
    case MISSING_CRITICAL_DATA = 'MISSING_CRITICAL_DATA';
    case SIMPLIFIED_MODEL_LIMITATION = 'SIMPLIFIED_MODEL_LIMITATION';
}
