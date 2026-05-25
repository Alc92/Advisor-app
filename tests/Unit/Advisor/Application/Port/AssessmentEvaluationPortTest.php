<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Port;

use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\ValueObject\Money;
use App\Tests\Support\Advisor\FakeAssessmentEvaluationPort;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AssessmentEvaluationPortTest extends TestCase
{
    public function test_fake_assessment_evaluation_port_implements_contract(): void
    {
        $fake = new FakeAssessmentEvaluationPort($this->buildResult());

        self::assertInstanceOf(AssessmentEvaluationPort::class, $fake);
    }

    public function test_fake_assessment_evaluation_port_returns_configured_result(): void
    {
        $configuredResult = $this->buildResult();
        $fake = new FakeAssessmentEvaluationPort($configuredResult);

        $result = $fake->evaluate($this->buildSnapshot(), $this->buildCatalog());

        self::assertSame($configuredResult, $result);
    }

    public function test_fake_assessment_evaluation_port_counts_calls(): void
    {
        $fake = new FakeAssessmentEvaluationPort($this->buildResult());
        $snapshot = $this->buildSnapshot();

        $fake->evaluate($snapshot, null);
        $fake->evaluate($snapshot, null);

        self::assertSame(2, $fake->calls());
        self::assertTrue($fake->wasCalled());
    }

    public function test_fake_assessment_evaluation_port_stores_last_snapshot(): void
    {
        $fake = new FakeAssessmentEvaluationPort($this->buildResult());
        $snapshot = $this->buildSnapshot();

        $fake->evaluate($snapshot, null);

        self::assertSame($snapshot, $fake->lastSnapshot());
    }

    public function test_fake_assessment_evaluation_port_stores_nullable_catalog(): void
    {
        $fake = new FakeAssessmentEvaluationPort($this->buildResult());
        $snapshot = $this->buildSnapshot();

        $fake->evaluate($snapshot, null);
        self::assertNull($fake->lastCatalog());

        $catalog = $this->buildCatalog();
        $fake->evaluate($snapshot, $catalog);
        self::assertSame($catalog, $fake->lastCatalog());
    }

    private function buildSnapshot(): AssessmentInputSnapshot
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

    private function buildResult(): AssessmentResult
    {
        $recommendation = new Recommendation(
            Decision::STAY,
            DecisionReasonCode::ALREADY_OPTIMIZED,
            null,
            null,
            null,
            new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Sin mejora clara.'),
            'Mantener situación actual.',
            [],
            [],
            null,
            [],
            null,
            null,
        );

        return new AssessmentResult(
            $recommendation,
            null,
            new DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }

    private function buildCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catalog-v1',
            'v1',
            [
                new PublishedOfferVersionForEvaluation(
                    'offer-version-1',
                    'Provider A',
                    'Plan 300',
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
                ),
            ],
        );
    }
}
