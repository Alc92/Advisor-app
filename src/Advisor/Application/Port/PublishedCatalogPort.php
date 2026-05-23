<?php

declare(strict_types=1);

namespace App\Advisor\Application\Port;

interface PublishedCatalogPort
{
    public function getCurrentPublishedCatalog(): PublishedCatalogForEvaluation;
}
