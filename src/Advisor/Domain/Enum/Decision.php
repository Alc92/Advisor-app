<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum Decision: string
{
    case SWITCH = 'SWITCH';
    case WAIT = 'WAIT';
    case STAY = 'STAY';
}
