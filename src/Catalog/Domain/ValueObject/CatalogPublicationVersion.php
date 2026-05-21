<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

use InvalidArgumentException;

final readonly class CatalogPublicationVersion
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('CatalogPublicationVersion cannot be empty.');
        }

        $this->value = $trimmed;
    }

    public function value(): string
    {
        return $this->value;
    }
}
