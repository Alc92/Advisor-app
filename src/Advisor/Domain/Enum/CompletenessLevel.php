<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum CompletenessLevel: string
{
    case MINIMUM_NOT_MET = 'MINIMUM_NOT_MET';
    case MINIMUM_MET = 'MINIMUM_MET';
    case RICH = 'RICH';
}
