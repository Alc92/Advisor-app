<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum PromotionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case NOT_ACTIVE = 'NOT_ACTIVE';
    case UNKNOWN = 'UNKNOWN';
}
