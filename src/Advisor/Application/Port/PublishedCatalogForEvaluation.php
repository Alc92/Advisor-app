<?php

declare(strict_types=1);

namespace App\Advisor\Application\Port;

use App\Catalog\Domain\ValueObject\CatalogPublicationId;
use InvalidArgumentException;

final readonly class PublishedCatalogForEvaluation
{
    /**
     * @var list<PublishedOfferVersionForEvaluation>
     */
    private array $offerVersions;

    private string $publicationVersion;

    /**
     * @param list<PublishedOfferVersionForEvaluation> $offerVersions
     */
    public function __construct(
        private CatalogPublicationId $publicationId,
        string $publicationVersion,
        array $offerVersions,
    ) {
        if (trim($this->publicationId->toString()) === '') {
            throw new InvalidArgumentException('publicationId cannot be empty.');
        }

        $trimmedPublicationVersion = trim($publicationVersion);
        if ($trimmedPublicationVersion === '') {
            throw new InvalidArgumentException('publicationVersion cannot be empty.');
        }

        if (!array_is_list($offerVersions)) {
            throw new InvalidArgumentException('offerVersions must be a list.');
        }

        if (count($offerVersions) === 0) {
            throw new InvalidArgumentException('offerVersions cannot be empty.');
        }

        foreach ($offerVersions as $offerVersion) {
            if (!$offerVersion instanceof PublishedOfferVersionForEvaluation) {
                throw new InvalidArgumentException('offerVersions must be a list of PublishedOfferVersionForEvaluation.');
            }
        }

        $this->publicationVersion = $trimmedPublicationVersion;
        $this->offerVersions = array_values($offerVersions);
    }

    public function publicationId(): CatalogPublicationId
    {
        return $this->publicationId;
    }

    public function publicationVersion(): string
    {
        return $this->publicationVersion;
    }

    /**
     * @return list<PublishedOfferVersionForEvaluation>
     */
    public function offerVersions(): array
    {
        return $this->offerVersions;
    }
}
