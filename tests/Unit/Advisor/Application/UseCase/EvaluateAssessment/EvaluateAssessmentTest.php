<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\UseCase\EvaluateAssessment;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\AssessmentRepository;
use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedCatalogPort;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Application\UseCase\EvaluateAssessment\EvaluateAssessment;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\EvaluationTrace;
use App\Advisor\Domain\Assessment\InputEvidenceSnapshot;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\MobileDataAdequacyLevel;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\MobileUsageDistribution;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\TriState;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;
use App\Shared\Application\Port\Clock;
use App\Shared\Application\Port\IdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EvaluateAssessmentTest extends TestCase
{
    public function test_it_does_not_query_published_catalog_when_minimum_input_is_not_met(): void
    {
        $evaluation = new RecordingAssessmentEvaluationPort(EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET);
        $catalogs = new RecordingPublishedCatalogPort($this->publishedCatalog());

        ($this->useCase($evaluation, $catalogs, new RecordingAssessmentRepository()))($this->commandWithoutMinimumInput());

        self::assertSame(0, $catalogs->calls);
        self::assertSame(1, $evaluation->calls);
        self::assertNull($evaluation->lastCatalog);
    }

    public function test_it_queries_published_catalog_once_when_minimum_input_is_met(): void
    {
        $catalog = $this->publishedCatalog();
        $evaluation = new RecordingAssessmentEvaluationPort(EvaluationMode::EVALUATED_NORMAL);
        $catalogs = new RecordingPublishedCatalogPort($catalog);

        ($this->useCase($evaluation, $catalogs, new RecordingAssessmentRepository()))($this->commandWithMinimumInput());

        self::assertSame(1, $catalogs->calls);
        self::assertSame(1, $evaluation->calls);
        self::assertSame($catalog, $evaluation->lastCatalog);
    }

    public function test_it_passes_additional_condition_profile_to_evaluator_snapshot(): void
    {
        $evaluation = new RecordingAssessmentEvaluationPort(EvaluationMode::EVALUATED_NORMAL);

        ($this->useCase($evaluation, new RecordingPublishedCatalogPort($this->publishedCatalog()), new RecordingAssessmentRepository()))(
            $this->commandWithMinimumInput([
                'fiberSpeedBandCurrent' => 'MBPS_600',
                'mobileDataAdequacyLevel' => 'GOING_FINE',
                'mobileUsageDistribution' => 'SIMILAR_USAGE',
                'tvImportance' => 'YES',
            ]),
        );

        $profile = $evaluation->lastSnapshot?->additionalConditionProfile();

        self::assertNotNull($profile);
        self::assertSame(MobileDataAdequacyLevel::GOING_FINE, $profile->mobileDataAdequacyLevel());
        self::assertSame(MobileUsageDistribution::SIMILAR_USAGE, $profile->mobileUsageDistribution());
        self::assertSame(TriState::YES, $profile->tvImportance());
        self::assertTrue($evaluation->lastSnapshot?->hasAdditionalConditionProfile());
    }

    public function test_it_marks_assessment_as_evaluated_with_real_evaluation_mode(): void
    {
        $repository = new RecordingAssessmentRepository();
        $assessmentId = AssessmentId::fromUuid(Uuid::fromString('44444444-4444-4444-4444-444444444444'));

        ($this->useCase(
            new RecordingAssessmentEvaluationPort(EvaluationMode::EVALUATED_DEGRADED),
            new RecordingPublishedCatalogPort($this->publishedCatalog()),
            $repository,
            $assessmentId,
        ))($this->commandWithMinimumInput());

        $assessment = $repository->get($assessmentId);

        self::assertNotNull($assessment);
        self::assertTrue($assessment->isEvaluated());
        self::assertSame(EvaluationMode::EVALUATED_DEGRADED, $assessment->evaluationMode());
        self::assertNotNull($assessment->result());
    }

    public function test_it_does_not_require_user(): void
    {
        $repository = new RecordingAssessmentRepository();
        $assessmentId = AssessmentId::fromUuid(Uuid::fromString('55555555-5555-5555-5555-555555555555'));

        $viewModel = ($this->useCase(
            new RecordingAssessmentEvaluationPort(EvaluationMode::EVALUATED_NORMAL),
            new RecordingPublishedCatalogPort($this->publishedCatalog()),
            $repository,
            $assessmentId,
        ))($this->commandWithMinimumInput());

        $assessment = $repository->get($assessmentId);

        self::assertSame(Decision::STAY->value, $viewModel->decision);
        self::assertNotNull($assessment);
        self::assertNull($assessment->userId());
    }

    private function useCase(
        RecordingAssessmentEvaluationPort $evaluation,
        RecordingPublishedCatalogPort $catalogs,
        RecordingAssessmentRepository $repository,
        ?AssessmentId $assessmentId = null,
    ): EvaluateAssessment {
        return new EvaluateAssessment(
            $evaluation,
            $catalogs,
            $repository,
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator($assessmentId?->toString() ?? '11111111-1111-1111-1111-111111111111'),
        );
    }

    /**
     * @param array<string, mixed>|null $additionalConditionProfile
     */
    private function commandWithMinimumInput(?array $additionalConditionProfile = null): EvaluateAssessmentCommand
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
            additionalConditionProfile: $additionalConditionProfile,
            captureChannel: 'WEB',
            captureExperienceMode: 'GUIDED',
        );
    }

    private function commandWithoutMinimumInput(): EvaluateAssessmentCommand
    {
        return new EvaluateAssessmentCommand(
            currentProvider: 'Provider A',
            productType: 'FIBER_MOBILE',
            approxMonthlyPriceAmount: '49.99',
            approxMonthlyPriceCurrency: 'EUR',
            mobileLinesCount: 2,
            mobileUsageBand: null,
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

    private function publishedCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'publication-1',
            'catalog-v1',
            [
                new PublishedOfferVersionForEvaluation(
                    'offer-version-1',
                    'Provider B',
                    'Mobile Plus',
                    new Money('29.99', 'EUR'),
                    2,
                    true,
                    600,
                    '50GB',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::STANDARD,
                    MobileUsageBand::HIGH,
                    true,
                    false,
                ),
            ],
        );
    }
}

final class RecordingAssessmentEvaluationPort implements AssessmentEvaluationPort
{
    public int $calls = 0;
    public ?AssessmentInputSnapshot $lastSnapshot = null;
    public ?PublishedCatalogForEvaluation $lastCatalog = null;

    public function __construct(private readonly EvaluationMode $evaluationMode)
    {
    }

    public function evaluate(AssessmentInputSnapshot $snapshot, ?PublishedCatalogForEvaluation $catalog): AssessmentResult
    {
        ++$this->calls;
        $this->lastSnapshot = $snapshot;
        $this->lastCatalog = $catalog;

        return new AssessmentResult(
            new Recommendation(
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
            ),
            $this->trace($snapshot),
            new DateTimeImmutable('2026-01-01 10:01:00'),
        );
    }

    private function trace(AssessmentInputSnapshot $snapshot): EvaluationTrace
    {
        $currentSituation = $snapshot->currentSituation();

        return new EvaluationTrace(
            $this->evaluationMode,
            [],
            [],
            [],
            [],
            [],
            null,
            null,
            null,
            [],
            new InputEvidenceSnapshot(
                $currentSituation->productType(),
                $currentSituation->approxMonthlyPrice(),
                $currentSituation->mobileUsageBand(),
                $currentSituation->fiberNeedBand(),
                $currentSituation->commitmentStatus(),
                $currentSituation->promotionStatus(),
                $snapshot->userPreference(),
                $snapshot->hasAdditionalConditionProfile(),
            ),
        );
    }
}

final class RecordingPublishedCatalogPort implements PublishedCatalogPort
{
    public int $calls = 0;

    public function __construct(private readonly PublishedCatalogForEvaluation $catalog)
    {
    }

    public function getCurrentPublishedCatalog(): PublishedCatalogForEvaluation
    {
        ++$this->calls;

        return $this->catalog;
    }
}

final class RecordingAssessmentRepository implements AssessmentRepository
{
    /** @var array<string, Assessment> */
    private array $assessments = [];

    public function save(Assessment $assessment): void
    {
        $this->assessments[$assessment->id()->toString()] = $assessment;
    }

    public function get(AssessmentId $id): ?Assessment
    {
        return $this->assessments[$id->toString()] ?? null;
    }
}

final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final readonly class FixedIdGenerator implements IdGenerator
{
    private Uuid $id;

    public function __construct(string $id)
    {
        $this->id = Uuid::fromString($id);
    }

    public function generate(): Uuid
    {
        return $this->id;
    }
}
