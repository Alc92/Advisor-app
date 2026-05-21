<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Catalog\Domain\ValueObject\CatalogPublicationId;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;

final readonly class CatalogPublicationItem
{
    public function __construct(
        private CatalogPublicationId $catalogPublicationId,
        private TelecomOfferVersionId $offerVersionId,
    ) {
    }

    public function catalogPublicationId(): CatalogPublicationId
    {
        return $this->catalogPublicationId;
    }

    public function offerVersionId(): TelecomOfferVersionId
    {
        return $this->offerVersionId;
    }
}
