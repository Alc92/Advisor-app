<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum DecisionReasonCode: string
{
    case CLEAR_SAVINGS = 'CLEAR_SAVINGS';
    case WAIT_FOR_COMMITMENT_END = 'WAIT_FOR_COMMITMENT_END';
    case WAIT_DUE_TO_UNCERTAINTY = 'WAIT_DUE_TO_UNCERTAINTY';
    case NO_CLEAR_IMPROVEMENT = 'NO_CLEAR_IMPROVEMENT';
    case TRADEOFF_NOT_WORTH_IT = 'TRADEOFF_NOT_WORTH_IT';
    case ALREADY_OPTIMIZED = 'ALREADY_OPTIMIZED';
}
