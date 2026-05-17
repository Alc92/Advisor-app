<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Enum;

enum DataProvenance: string
{
    case DECLARED_BY_USER = 'DECLARED_BY_USER';
    case INVOICE_SUPPORTED = 'INVOICE_SUPPORTED';
    case MIXED = 'MIXED';
}
