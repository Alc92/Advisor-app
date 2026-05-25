<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared;

use App\Shared\Application\Port\IdGenerator;
use LogicException;
use Symfony\Component\Uid\Uuid;

final class FixedIdGenerator implements IdGenerator
{
    /**
     * @var list<Uuid>
     */
    private array $ids;

    public function __construct(Uuid ...$ids)
    {
        if (count($ids) === 0) {
            throw new LogicException('FixedIdGenerator requires at least one UUID.');
        }

        $this->ids = array_values($ids);
    }

    public function generate(): Uuid
    {
        if ($this->ids === []) {
            throw new LogicException('No UUIDs remaining in FixedIdGenerator.');
        }

        /** @var Uuid $id */
        $id = array_shift($this->ids);

        return $id;
    }

    public function remaining(): int
    {
        return count($this->ids);
    }
}
