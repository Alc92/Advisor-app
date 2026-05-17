<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum MobileDataAdequacyLevel: string
{
    case MORE_THAN_ENOUGH = 'MORE_THAN_ENOUGH';
    case GOING_FINE = 'GOING_FINE';
    case SOMETIMES_SHORT = 'SOMETIMES_SHORT';
    case UNKNOWN = 'UNKNOWN';
}
