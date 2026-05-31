<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\UseCase\EvaluateAssessment;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\UseCase\EvaluateAssessment\EvaluateAssessment;
use App\Advisor\Application\ViewModel\AssessmentResultViewModel;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Tests\Support\Advisor\FakeAssessmentEvaluationPort;
use App\Tests\Support\Advisor\InMemoryAssessmentRepository;
use App\Tests\Support\Shared\FixedClock;
use App\Tests\Support\Shared\FixedIdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EvaluateAssessmentTest extends TestCase
{
    public function testEvaluateAssessmentCanBeExecutedWithValidCommand(): void
    {
        $useCase = new EvaluateAssessment(
            new FakeAssessmentEvaluationPort($this->buildResult()),
            new InMemoryAssessmentRepository(),
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator(Uuid::fromString('11111111-1111-1111-1111-111111111111')),
        );

        $result = $useCase($this->buildValidCommand());

        self::assertInstanceOf(AssessmentResultViewModel::class, $result);
    }

    public function testEvaluateAssessmentInvokesAssessmentEvaluationPortWithNullCatalog(): void
    {
        $evaluation = new FakeAssessmentEvaluationPort($this->buildResult());
        $useCase = new EvaluateAssessment(
            $evaluation,
            new InMemoryAssessmentRepository(),
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator(Uuid::fromString('11111111-1111-1111-1111-111111111111')),
        );

        $useCase($this->buildValidCommand());

        self::assertTrue($evaluation->wasCalled());
        self::assertInstanceOf(AssessmentInputSnapshot::class, $evaluation->lastSnapshot());
        self::assertNull($evaluation->lastCatalog());
    }

    public function testEvaluateAssessmentStoresAssessmentTechnically(): void
    {
        $repository = new InMemoryAssessmentRepository();
        $generatedId = Uuid::fromString('22222222-2222-2222-2222-222222222222');
        $useCase = new EvaluateAssessment(
            new FakeAssessmentEvaluationPort($this->buildResult()),
            $repository,
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator($generatedId),
        );

        $useCase($this->buildValidCommand());

        $stored = $repository->get(AssessmentId::fromUuid($generatedId));
        self::assertNotNull($stored);
        self::assertSame($generatedId->toString(), $stored->id()->toString());
    }

    public function testEvaluateAssessmentReturnsViewModelWithGeneratedIdAndNotFunctionallyPersisted(): void
    {
        $generatedId = Uuid::fromString('33333333-3333-3333-3333-333333333333');
        $useCase = new EvaluateAssessment(
            new FakeAssessmentEvaluationPort($this->buildResult()),
            new InMemoryAssessmentRepository(),
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator($generatedId),
        );

        $viewModel = $useCase($this->buildValidCommand());

        self::assertSame($generatedId->toString(), $viewModel->assessmentId);
        self::assertFalse($viewModel->isPersistedFunctionally);
        self::assertNull($viewModel->suggestedOffer);
        self::assertSame([], $viewModel->tradeOffs);
        self::assertSame([], $viewModel->risks);
        self::assertSame([], $viewModel->analysisLimitations);
        self::assertNull($viewModel->waitKind);
        self::assertNull($viewModel->recommendedReviewMoment);
        self::assertNull($viewModel->reviewTrigger);
        self::assertNull($viewModel->estimatedImpactSummary);
    }

    public function testEvaluateAssessmentDoesNotReferenceGateTwoConcerns(): void
    {
        $useCaseFile = __DIR__ . '/../../../../../../src/Advisor/Application/UseCase/EvaluateAssessment/EvaluateAssessment.php';
        $content = file_get_contents($useCaseFile);

        self::assertIsString($content);
        self::assertStringNotContainsString('Identity', $content);
        self::assertStringNotContainsString('RegisterUser', $content);
        self::assertStringNotContainsString('LoginUser', $content);
        self::assertStringNotContainsString('SaveAssessmentResult', $content);
        self::assertStringNotContainsString('SaveSwitchRecommendation', $content);
        self::assertStringNotContainsString('SavedAssessment', $content);
        self::assertStringNotContainsString('Controller', $content);
        self::assertStringNotContainsString('Twig', $content);
        self::assertStringNotContainsString('Doctrine', $content);
        self::assertStringNotContainsString('PublishedCatalogPort', $content);
        self::assertStringNotContainsString('MinimumEvaluableInputRule', $content);
        self::assertStringNotContainsString('RuleEnginePort', $content);
    }

    private function buildValidCommand(): EvaluateAssessmentCommand
    {
        return new EvaluateAssessmentCommand(
            currentProvider: 'Provider A',
            productType: 'FIBER_MOBILE',
            approxMonthlyPriceAmount: '49.99',
            approxMonthlyPriceCurrency: 'EUR',
            mobileLinesCount: 2,
            mobileUsageBand: 'HIGH',
            fiberNeedBand: 'STANDARD',
            commitmentStatus: 'YES',
            commitmentEndYear: 2026,
            commitmentEndMonth: 6,
            promotionStatus: 'ACTIVE',
            promotionEndYear: 2026,
            promotionEndMonth: 8,
            tvIncluded: true,
            multipleResidencesDetected: true,
            dataProvenance: 'DECLARED_BY_USER',
            userPreference: 'BALANCE',
            additionalConditionProfile: null,
            captureChannel: 'WEB',
            captureExperienceMode: 'GUIDED',
        );
    }

    private function buildResult(): AssessmentResult
    {
        $recommendation = new Recommendation(
            Decision::STAY,
            DecisionReasonCode::NO_CLEAR_IMPROVEMENT,
            null,
            null,
            null,
            new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Sin mejora clara.'),
            'Mantener situacion actual.',
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
            new DateTimeImmutable('2026-01-01 10:01:00'),
        );
    }
}
