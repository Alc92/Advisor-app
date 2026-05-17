<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum ProductType: string
{
    case MOBILE = 'MOBILE';
    case FIBER = 'FIBER';
    case FIBER_MOBILE = 'FIBER_MOBILE';
}
