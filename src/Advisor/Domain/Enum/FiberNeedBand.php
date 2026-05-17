<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum FiberNeedBand: string
{
    case BASIC = 'BASIC';
    case STANDARD = 'STANDARD';
    case HIGH = 'HIGH';
}
