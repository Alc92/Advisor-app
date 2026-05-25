<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Port;

use App\Advisor\Application\Port\AssessmentRepository;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\Money;
use App\Tests\Support\Advisor\InMemoryAssessmentRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AssessmentRepositoryTest extends TestCase
{
    public function test_in_memory_assessment_repository_implements_contract(): void
    {
        $repository = new InMemoryAssessmentRepository();

        self::assertInstanceOf(AssessmentRepository::class, $repository);
    }

    public function test_in_memory_assessment_repository_saves_and_gets_assessment_by_id(): void
    {
        $repository = new InMemoryAssessmentRepository();
        $assessment = $this->buildAssessment(AssessmentId::fromUuid(Uuid::v4()));

        $repository->save($assessment);

        self::assertSame($assessment, $repository->get($assessment->id()));
    }

    public function test_in_memory_assessment_repository_returns_null_when_assessment_does_not_exist(): void
    {
        $repository = new InMemoryAssessmentRepository();

        self::assertNull($repository->get(AssessmentId::fromUuid(Uuid::v4())));
    }

    public function test_in_memory_assessment_repository_overwrites_same_assessment_id_without_duplicating(): void
    {
        $repository = new InMemoryAssessmentRepository();
        $assessment = $this->buildAssessment(AssessmentId::fromUuid(Uuid::v4()));

        $repository->save($assessment);
        $repository->save($assessment);

        self::assertSame(1, $repository->count());
    }

    private function buildAssessment(AssessmentId $id): Assessment
    {
        return Assessment::createEphemeral(
            $id,
            $this->buildInputSnapshot(),
            new DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }

    private function buildInputSnapshot(): AssessmentInputSnapshot
    {
        $currentSituation = new CurrentSituation(
            'Test Provider',
            ProductType::MOBILE,
            new Money('20', 'EUR'),
            1,
            null,
            null,
            CommitmentStatus::NO,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            false,
            false,
            DataProvenance::DECLARED_BY_USER,
        );

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );
    }
}
