<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Catalog\Domain\Enum\CatalogPublicationStatus;
use App\Catalog\Domain\ValueObject\CatalogPublicationId;
use App\Catalog\Domain\ValueObject\CatalogPublicationVersion;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use DateTimeImmutable;
use InvalidArgumentException;

final class CatalogPublication
{
    private CatalogPublicationId $id;
    private CatalogPublicationVersion $version;
    private CatalogPublicationStatus $status;
    private ?DateTimeImmutable $publishedAt;

    /**
     * @var list<CatalogPublicationItem>
     */
    private array $items;

    /**
     * @param list<CatalogPublicationItem> $items
     */
    private function __construct(
        CatalogPublicationId $id,
        CatalogPublicationVersion $version,
        CatalogPublicationStatus $status,
        ?DateTimeImmutable $publishedAt,
        array $items,
    ) {
        $this->validateInvariantStatusPublishedAt($status, $publishedAt);

        $this->id = $id;
        $this->version = $version;
        $this->status = $status;
        $this->publishedAt = $publishedAt;
        $this->items = $this->validateItems($items);
    }

    public static function createDraft(
        CatalogPublicationId $id,
        CatalogPublicationVersion $version,
    ): self {
        return new self(
            $id,
            $version,
            CatalogPublicationStatus::DRAFT,
            null,
            [],
        );
    }

    public function addItem(TelecomOfferVersionId $offerVersionId): void
    {
        if ($this->status !== CatalogPublicationStatus::DRAFT) {
            throw new InvalidArgumentException('Items can only be added to a DRAFT publication.');
        }

        foreach ($this->items as $item) {
            if ($item->offerVersionId()->toString() === $offerVersionId->toString()) {
                throw new InvalidArgumentException('Duplicate offer version ID in publication.');
            }
        }

        $this->items[] = new CatalogPublicationItem($this->id, $offerVersionId);
    }

    public function publish(DateTimeImmutable $publishedAt): void
    {
        if ($this->status !== CatalogPublicationStatus::DRAFT) {
            throw new InvalidArgumentException('Only DRAFT publications can be published.');
        }

        if (count($this->items) === 0) {
            throw new InvalidArgumentException('Cannot publish a publication without items.');
        }

        $this->status = CatalogPublicationStatus::PUBLISHED;
        $this->publishedAt = $publishedAt;
    }

    public function isEvaluable(): bool
    {
        return $this->status === CatalogPublicationStatus::PUBLISHED;
    }

    public function id(): CatalogPublicationId
    {
        return $this->id;
    }

    public function version(): CatalogPublicationVersion
    {
        return $this->version;
    }

    public function status(): CatalogPublicationStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @return list<CatalogPublicationItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @param list<CatalogPublicationItem> $items
     * @return list<CatalogPublicationItem>
     */
    private function validateItems(array $items): array
    {
        $result = [];
        $seenIds = [];

        foreach ($items as $item) {
            if (!$item instanceof CatalogPublicationItem) {
                throw new InvalidArgumentException('Each item must be a CatalogPublicationItem.');
            }

            if ($item->catalogPublicationId()->toString() !== $this->id->toString()) {
                throw new InvalidArgumentException('Item must belong to this publication.');
            }

            $key = $item->offerVersionId()->toString();

            if (isset($seenIds[$key])) {
                throw new InvalidArgumentException('Duplicate offer version ID in publication.');
            }

            $seenIds[$key] = true;
            $result[] = $item;
        }

        return array_values($result);
    }

    private function validateInvariantStatusPublishedAt(
        CatalogPublicationStatus $status,
        ?DateTimeImmutable $publishedAt,
    ): void {
        if ($status === CatalogPublicationStatus::DRAFT && $publishedAt !== null) {
            throw new InvalidArgumentException('DRAFT publication must have null publishedAt.');
        }

        if ($status === CatalogPublicationStatus::PUBLISHED && $publishedAt === null) {
            throw new InvalidArgumentException('PUBLISHED publication must have a publishedAt.');
        }
    }
}
