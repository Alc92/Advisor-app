<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class TelecomOfferId
{
    private function __construct(private Uuid $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('TelecomOfferId cannot be empty.');
        }

        try {
            $uuid = Uuid::fromString($trimmed);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('TelecomOfferId must be a valid UUID.', 0, $e);
        }

        return new self($uuid);
    }

    public static function fromUuid(Uuid $uuid): self
    {
        return new self($uuid);
    }

    public function value(): Uuid
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->toRfc4122();
    }
}
