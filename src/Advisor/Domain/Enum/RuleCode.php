<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum RuleCode: string
{
    case MINIMUM_DATA_CHECK = 'MINIMUM_DATA_CHECK';
    case FIT_FILTER = 'FIT_FILTER';
    case SAVINGS_THRESHOLD = 'SAVINGS_THRESHOLD';
    case FRICTION_CHECK = 'FRICTION_CHECK';
    case PREFERENCE_MODULATION = 'PREFERENCE_MODULATION';
    case WAIT_DEGRADATION = 'WAIT_DEGRADATION';
    case FINAL_SELECTION = 'FINAL_SELECTION';
}
