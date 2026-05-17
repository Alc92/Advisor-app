<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum TriState: string
{
    case YES = 'YES';
    case NO = 'NO';
    case UNKNOWN = 'UNKNOWN';
}
