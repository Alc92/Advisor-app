<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum UncertaintyFlag: string
{
    case UNKNOWN_COMMITMENT = 'UNKNOWN_COMMITMENT';
    case UNKNOWN_PROMOTION = 'UNKNOWN_PROMOTION';
    case UNKNOWN_TV = 'UNKNOWN_TV';
    case MISSING_USAGE = 'MISSING_USAGE';
    case MULTI_RESIDENCE = 'MULTI_RESIDENCE';
    case SIMPLIFIED_MODEL_LIMITATION = 'SIMPLIFIED_MODEL_LIMITATION';
}
