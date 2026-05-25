<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Port;

use App\Shared\Application\Port\IdGenerator;
use App\Tests\Support\Shared\FixedIdGenerator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class IdGeneratorTest extends TestCase
{
    public function test_fixed_id_generator_implements_id_generator_contract(): void
    {
        $generator = new FixedIdGenerator(Uuid::fromString('11111111-1111-1111-1111-111111111111'));

        self::assertInstanceOf(IdGenerator::class, $generator);
    }

    public function test_fixed_id_generator_returns_configured_uuids_in_order(): void
    {
        $first = Uuid::fromString('11111111-1111-1111-1111-111111111111');
        $second = Uuid::fromString('22222222-2222-2222-2222-222222222222');
        $third = Uuid::fromString('33333333-3333-3333-3333-333333333333');

        $generator = new FixedIdGenerator($first, $second, $third);

        self::assertSame($first, $generator->generate());
        self::assertSame($second, $generator->generate());
        self::assertSame($third, $generator->generate());
    }

    public function test_fixed_id_generator_exposes_remaining_ids_count(): void
    {
        $generator = new FixedIdGenerator(
            Uuid::fromString('11111111-1111-1111-1111-111111111111'),
            Uuid::fromString('22222222-2222-2222-2222-222222222222'),
        );

        self::assertSame(2, $generator->remaining());
        $generator->generate();
        self::assertSame(1, $generator->remaining());
        $generator->generate();
        self::assertSame(0, $generator->remaining());
    }

    public function test_fixed_id_generator_fails_when_ids_are_exhausted(): void
    {
        $generator = new FixedIdGenerator(Uuid::fromString('11111111-1111-1111-1111-111111111111'));
        $generator->generate();

        $this->expectException(LogicException::class);

        $generator->generate();
    }
}
