<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

use Symfony\Component\Uid\Uuid;

interface IdGenerator
{
    public function generate(): Uuid;
}
