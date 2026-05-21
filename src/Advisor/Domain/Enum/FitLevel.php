<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum FitLevel: string
{
    case HIGH = 'HIGH';
    case MEDIUM = 'MEDIUM';
    case LOW = 'LOW';
}
