<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Enum;

enum CatalogPublicationStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';
}
