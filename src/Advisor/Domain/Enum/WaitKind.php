<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum WaitKind: string
{
    case TIMING = 'TIMING';
    case UNCERTAINTY_OR_MISSING_INFO = 'UNCERTAINTY_OR_MISSING_INFO';
}
