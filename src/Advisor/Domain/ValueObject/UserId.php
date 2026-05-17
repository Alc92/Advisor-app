<?php

declare(strict_types=1);

namespace App\Advisor\Domain\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class UserId
{
    private function __construct(private Uuid $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('UserId cannot be empty.');
        }

        try {
            $uuid = Uuid::fromString($trimmed);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('UserId must be a valid UUID.', 0, $e);
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
        return $this->value->toString();
    }
}
