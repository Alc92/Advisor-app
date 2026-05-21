<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum ImpactType: string
{
    case MONTHLY_SAVINGS = 'MONTHLY_SAVINGS';
    case COST_AVOIDED = 'COST_AVOIDED';
    case SERVICE_IMPROVEMENT = 'SERVICE_IMPROVEMENT';
    case NO_CLEAR_IMPACT = 'NO_CLEAR_IMPACT';
}
