<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum MobileUsageDistribution: string
{
    case SIMILAR_USAGE = 'SIMILAR_USAGE';
    case ONE_LINE_USES_MUCH_MORE = 'ONE_LINE_USES_MUCH_MORE';
    case UNKNOWN = 'UNKNOWN';
}
