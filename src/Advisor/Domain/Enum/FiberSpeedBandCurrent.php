<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum FiberSpeedBandCurrent: string
{
    case UNKNOWN = 'UNKNOWN';
    case UP_TO_300_MB = 'UP_TO_300_MB';
    case MBPS_600 = 'MBPS_600';
    case GBPS_1_OR_MORE = 'GBPS_1_OR_MORE';
}
