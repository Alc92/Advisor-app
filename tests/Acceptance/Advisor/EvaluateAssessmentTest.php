<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Advisor;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\UseCase\EvaluateAssessment\EvaluateAssessment;
use App\Advisor\Application\ViewModel\AssessmentResultViewModel;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\Money;
use App\Tests\Support\Advisor\FakeAssessmentEvaluationPort;
use App\Tests\Support\Advisor\FakePublishedCatalogPort;
use App\Tests\Support\Advisor\InMemoryAssessmentRepository;
use App\Tests\Support\Shared\FixedClock;
use App\Tests\Support\Shared\FixedIdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EvaluateAssessmentTest extends TestCase
{
    private const ASSESSMENT_UUID = '11111111-1111-1111-1111-111111111111';

    public function testItPropagatesSwitchDecisionFromAssessmentEvaluator(): void
    {
        $evaluationPort = new FakeAssessmentEvaluationPort($this->resultWithDecision(Decision::SWITCH));
        $repository = new InMemoryAssessmentRepository();
        $useCase = $this->useCaseWith($evaluationPort, $repository);

        $result = $useCase($this->validCommand());

        self::assertInstanceOf(AssessmentResultViewModel::class, $result);
        self::assertSame('SWITCH', $result->decision);
        self::assertFalse($result->isPersistedFunctionally);
        self::assertSame(1, $evaluationPort->calls());
    }

    public function testItPropagatesWaitDecisionFromAssessmentEvaluator(): void
    {
        $evaluationPort = new FakeAssessmentEvaluationPort($this->resultWithDecision(Decision::WAIT));
        $repository = new InMemoryAssessmentRepository();
        $useCase = $this->useCaseWith($evaluationPort, $repository);

        $result = $useCase($this->validCommand());

        self::assertInstanceOf(AssessmentResultViewModel::class, $result);
        self::assertSame('WAIT', $result->decision);
        self::assertFalse($result->isPersistedFunctionally);
        self::assertSame(1, $evaluationPort->calls());
    }

    public function testItPropagatesStayDecisionFromAssessmentEvaluator(): void
    {
        $evaluationPort = new FakeAssessmentEvaluationPort($this->resultWithDecision(Decision::STAY));
        $repository = new InMemoryAssessmentRepository();
        $useCase = $this->useCaseWith($evaluationPort, $repository);

        $result = $useCase($this->validCommand());

        self::assertInstanceOf(AssessmentResultViewModel::class, $result);
        self::assertSame('STAY', $result->decision);
        self::assertFalse($result->isPersistedFunctionally);
        self::assertSame(1, $evaluationPort->calls());
    }

    public function testItStoresTheTechnicalAssessment(): void
    {
        $evaluationPort = new FakeAssessmentEvaluationPort($this->resultWithDecision(Decision::STAY));
        $repository = new InMemoryAssessmentRepository();
        $useCase = $this->useCaseWith($evaluationPort, $repository);

        $result = $useCase($this->validCommand());

        $stored = $repository->get(AssessmentId::fromString(self::ASSESSMENT_UUID));

        self::assertNotNull($stored);
        self::assertSame(1, $repository->count());
        self::assertSame(self::ASSESSMENT_UUID, $result->assessmentId);
        self::assertFalse($result->isPersistedFunctionally);
    }

    private function validCommand(): EvaluateAssessmentCommand
    {
        return new EvaluateAssessmentCommand(
            currentProvider: 'Provider A',
            productType: 'FIBER_MOBILE',
            approxMonthlyPriceAmount: '49.99',
            approxMonthlyPriceCurrency: 'EUR',
            mobileLinesCount: 2,
            mobileUsageBand: 'HIGH',
            fiberNeedBand: 'STANDARD',
            commitmentStatus: 'NO',
            commitmentEndYear: null,
            commitmentEndMonth: null,
            promotionStatus: 'NOT_ACTIVE',
            promotionEndYear: null,
            promotionEndMonth: null,
            tvIncluded: true,
            multipleResidencesDetected: false,
            dataProvenance: 'DECLARED_BY_USER',
            userPreference: 'BALANCE',
            additionalConditionProfile: null,
            captureChannel: 'WEB',
            captureExperienceMode: 'GUIDED',
        );
    }

    private function useCaseWith(
        FakeAssessmentEvaluationPort $evaluationPort,
        InMemoryAssessmentRepository $repository,
    ): EvaluateAssessment {
        return new EvaluateAssessment(
            $evaluationPort,
            new FakePublishedCatalogPort(),
            $repository,
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator(Uuid::fromString(self::ASSESSMENT_UUID)),
        );
    }

    private function resultWithDecision(Decision $decision): AssessmentResult
    {
        $recommendation = match ($decision) {
            Decision::SWITCH => new Recommendation(
                Decision::SWITCH,
                DecisionReasonCode::CLEAR_SAVINGS,
                null,
                Uuid::fromString('22222222-2222-2222-2222-222222222222'),
                new RecommendedOfferSnapshot(
                    'Telco Switch',
                    'Plan Switch',
                    new Money('29.99', 'EUR'),
                    1,
                    false,
                    300,
                    '30 GB',
                ),
                new EstimatedImpact(null, null, ImpactType::MONTHLY_SAVINGS, 'Ahorro estimado.'),
                'Cambio recomendado por el evaluador fake.',
                [],
                [],
                null,
                [],
                null,
                null,
            ),
            Decision::WAIT => new Recommendation(
                Decision::WAIT,
                DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
                WaitKind::UNCERTAINTY_OR_MISSING_INFO,
                null,
                null,
                new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Sin impacto claro.'),
                'Espera recomendada por el evaluador fake.',
                [],
                [],
                null,
                [],
                null,
                ReviewTrigger::CHECK_MISSING_INFORMATION,
            ),
            Decision::STAY => new Recommendation(
                Decision::STAY,
                DecisionReasonCode::NO_CLEAR_IMPROVEMENT,
                null,
                null,
                null,
                new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Sin mejora clara.'),
                'Mantener recomendado por el evaluador fake.',
                [],
                [],
                null,
                [],
                null,
                null,
            ),
        };

        return new AssessmentResult(
            $recommendation,
            null,
            new DateTimeImmutable('2026-01-01 10:01:00'),
        );
    }
}
