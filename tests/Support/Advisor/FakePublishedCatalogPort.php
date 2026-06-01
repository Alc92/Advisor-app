<?php

declare(strict_types=1);

namespace App\Tests\Support\Advisor;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedCatalogPort;
use App\Tests\Support\Catalog\Gate1PublishedCatalogFixture;

final class FakePublishedCatalogPort implements PublishedCatalogPort
{
    private int $calls = 0;

    public function __construct(
        private ?PublishedCatalogForEvaluation $catalog = null,
    ) {
    }

    public function getCurrentPublishedCatalog(): PublishedCatalogForEvaluation
    {
        ++$this->calls;

        return $this->catalog ?? Gate1PublishedCatalogFixture::publishedCatalog();
    }

    public function calls(): int
    {
        return $this->calls;
    }

    public function wasCalled(): bool
    {
        return $this->calls > 0;
    }
}
