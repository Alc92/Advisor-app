<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum AssessmentStatus: string
{
    case CREATED = 'CREATED';
    case EVALUATED = 'EVALUATED';
}
