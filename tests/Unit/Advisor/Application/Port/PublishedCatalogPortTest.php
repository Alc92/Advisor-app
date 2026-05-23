<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Port;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\CatalogPublicationId;
use App\Catalog\Domain\ValueObject\TelecomOfferVersionId;
use App\Tests\Support\Advisor\FakePublishedCatalogPort;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishedCatalogPortTest extends TestCase
{
    public function test_published_catalog_for_evaluation_rejects_publication_id_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedCatalogForEvaluation(
            CatalogPublicationId::fromString(''),
            'v1',
            [$this->buildValidOfferVersion()],
        );
    }

    public function test_published_catalog_for_evaluation_rejects_offer_versions_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedCatalogForEvaluation(
            CatalogPublicationId::fromUuid(Uuid::v4()),
            'v1',
            [],
        );
    }

    #[DataProvider('invalidOfferVersionDataProvider')]
    public function test_published_offer_version_for_evaluation_rejects_invalid_identity_or_names(
        callable $offerVersionIdFactory,
        string $provider,
        string $commercialName,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new PublishedOfferVersionForEvaluation(
            $offerVersionIdFactory(),
            $provider,
            $commercialName,
            new Money('35', 'EUR'),
            1,
            false,
            null,
            null,
            ProductType::FIBER,
            null,
            null,
            true,
            false,
        );
    }

    public static function invalidOfferVersionDataProvider(): iterable
    {
        yield 'empty offerVersionId' => [
            static fn () => TelecomOfferVersionId::fromString(''),
            'Provider A',
            'Plan 300',
        ];

        yield 'empty provider' => [
            static fn () => TelecomOfferVersionId::fromUuid(Uuid::v4()),
            '   ',
            'Plan 300',
        ];

        yield 'empty commercialName' => [
            static fn () => TelecomOfferVersionId::fromUuid(Uuid::v4()),
            'Provider A',
            '   ',
        ];
    }

    public function test_published_offer_version_for_evaluation_rejects_mobile_lines_included_less_or_equal_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedOfferVersionForEvaluation(
            TelecomOfferVersionId::fromUuid(Uuid::v4()),
            'Provider A',
            'Plan 300',
            new Money('35', 'EUR'),
            0,
            false,
            null,
            null,
            ProductType::FIBER,
            null,
            null,
            true,
            false,
        );
    }

    public function test_fake_published_catalog_port_returns_catalog_and_counts_calls(): void
    {
        $catalog = new PublishedCatalogForEvaluation(
            CatalogPublicationId::fromUuid(Uuid::v4()),
            'v1',
            [$this->buildValidOfferVersion()],
        );

        $fake = new FakePublishedCatalogPort($catalog);

        self::assertSame(0, $fake->calls());
        self::assertFalse($fake->wasCalled());

        $returned = $fake->getCurrentPublishedCatalog();

        self::assertSame($catalog, $returned);
        self::assertSame(1, $fake->calls());
        self::assertTrue($fake->wasCalled());
    }

    private function buildValidOfferVersion(): PublishedOfferVersionForEvaluation
    {
        return new PublishedOfferVersionForEvaluation(
            TelecomOfferVersionId::fromUuid(Uuid::v4()),
            'Provider A',
            'Plan 300',
            new Money('35', 'EUR'),
            1,
            false,
            300,
            '50 GB',
            ProductType::FIBER,
            null,
            null,
            true,
            false,
        );
    }
}
