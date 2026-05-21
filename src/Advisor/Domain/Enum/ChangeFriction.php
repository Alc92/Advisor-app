<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum ChangeFriction: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
}
