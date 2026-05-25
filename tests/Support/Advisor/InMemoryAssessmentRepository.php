<?php

declare(strict_types=1);

namespace App\Tests\Support\Advisor;

use App\Advisor\Application\Port\AssessmentRepository;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\ValueObject\AssessmentId;

final class InMemoryAssessmentRepository implements AssessmentRepository
{
    /**
     * @var array<string, Assessment>
     */
    private array $items = [];

    public function save(Assessment $assessment): void
    {
        $this->items[$this->keyFromId($assessment->id())] = $assessment;
    }

    public function get(AssessmentId $id): ?Assessment
    {
        return $this->items[$this->keyFromId($id)] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    private function keyFromId(AssessmentId $id): string
    {
        return $id->toString();
    }
}
