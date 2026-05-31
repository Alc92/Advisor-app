<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Port;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedCatalogPort;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Tests\Support\Advisor\FakePublishedCatalogPort;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublishedCatalogPortTest extends TestCase
{
    public function test_published_catalog_for_evaluation_rejects_publication_id_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedCatalogForEvaluation(
            '',
            'v1',
            [$this->buildValidOfferVersion()],
        );
    }

    public function test_published_catalog_for_evaluation_rejects_offer_versions_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedCatalogForEvaluation(
            'b6bb628e-2e32-4c67-a676-61cb4ec7afaf',
            'v1',
            [],
        );
    }

    #[DataProvider('invalidOfferVersionDataProvider')]
    public function test_published_offer_version_for_evaluation_rejects_invalid_identity_or_names(
        string $offerVersionId,
        string $provider,
        string $commercialName,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new PublishedOfferVersionForEvaluation(
            $offerVersionId,
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
            '',
            'Provider A',
            'Plan 300',
        ];

        yield 'empty provider' => [
            'b7ecf814-2987-4a9f-b19d-81fdde8f9194',
            '   ',
            'Plan 300',
        ];

        yield 'empty commercialName' => [
            'b7ecf814-2987-4a9f-b19d-81fdde8f9194',
            'Provider A',
            '   ',
        ];
    }

    public function test_published_offer_version_for_evaluation_rejects_mobile_lines_included_less_than_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublishedOfferVersionForEvaluation(
            '0eb4f6e1-8985-4d33-9102-c68ea6d14e72',
            'Provider A',
            'Plan 300',
            new Money('35', 'EUR'),
            -1,
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

    public function test_published_offer_version_for_evaluation_accepts_zero_mobile_lines_included(): void
    {
        $offerVersion = new PublishedOfferVersionForEvaluation(
            '8660f20a-b5de-4eb7-bfdb-76ce6658b2dd',
            'Provider A',
            'Plan fibra 300',
            new Money('35', 'EUR'),
            0,
            false,
            300,
            null,
            ProductType::FIBER,
            null,
            null,
            true,
            false,
        );

        self::assertSame(0, $offerVersion->mobileLinesIncluded());
    }

    public function test_fake_published_catalog_port_returns_catalog_and_counts_calls(): void
    {
        $fake = new FakePublishedCatalogPort();

        self::assertInstanceOf(PublishedCatalogPort::class, $fake);
        self::assertSame(0, $fake->calls());
        self::assertFalse($fake->wasCalled());

        $returned = $fake->getCurrentPublishedCatalog();

        self::assertInstanceOf(PublishedCatalogForEvaluation::class, $returned);
        self::assertSame('catpub_gate1_001', $returned->publicationId());
        self::assertSame('gate1-fixture-2026-05-31', $returned->publicationVersion());
        self::assertSame(1, $fake->calls());
        self::assertTrue($fake->wasCalled());
    }

    private function buildValidOfferVersion(): PublishedOfferVersionForEvaluation
    {
        return new PublishedOfferVersionForEvaluation(
            'f681dc8c-9e02-4fcd-9941-0f904d494f63',
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
