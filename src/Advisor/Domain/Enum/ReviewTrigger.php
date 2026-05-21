<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum ReviewTrigger: string
{
    case COMMITMENT_END = 'COMMITMENT_END';
    case PROMOTION_END = 'PROMOTION_END';
    case CHECK_MISSING_INFORMATION = 'CHECK_MISSING_INFORMATION';
    case USER_REQUESTED_REVIEW = 'USER_REQUESTED_REVIEW';
}
