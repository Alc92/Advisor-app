<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum UserPreference: string
{
    case SAVINGS = 'SAVINGS';
    case BALANCE = 'BALANCE';
    case KEEP_CONDITIONS = 'KEEP_CONDITIONS';
}
