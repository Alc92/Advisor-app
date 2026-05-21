<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Enum;

enum FiberCapacityBand: string
{
    case BASIC = 'BASIC';
    case STANDARD = 'STANDARD';
    case HIGH = 'HIGH';
}
