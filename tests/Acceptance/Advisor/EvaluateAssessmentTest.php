<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Advisor;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Service\AssessmentEvaluator;
use App\Advisor\Application\Service\PublishedOfferEvaluationAssembler;
use App\Advisor\Application\UseCase\EvaluateAssessment\EvaluateAssessment;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Rule\AlternativeSelector;
use App\Advisor\Domain\Rule\ChangeFrictionCalculator;
use App\Advisor\Domain\Rule\EstimatedImpactCalculator;
use App\Advisor\Domain\Rule\EvaluationTraceBuilder;
use App\Advisor\Domain\Rule\FitCalculator;
use App\Advisor\Domain\Rule\MinimumEvaluableInputRule;
use App\Advisor\Domain\Rule\StayRecommendationBuilder;
use App\Advisor\Domain\Rule\SwitchRecommendationBuilder;
use App\Advisor\Domain\Rule\WaitRecommendationBuilder;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use App\Tests\Support\Advisor\FakePublishedCatalogPort;
use App\Tests\Support\Advisor\InMemoryAssessmentRepository;
use App\Tests\Support\Catalog\Gate1PublishedCatalogFixture;
use App\Tests\Support\Shared\FixedClock;
use App\Tests\Support\Shared\FixedIdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class EvaluateAssessmentTest extends TestCase
{
    private const ASSESSMENT_UUID = '11111111-1111-1111-1111-111111111111';

    public function test_it_returns_switch_using_real_evaluator_and_published_catalog(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::clearSavingsCatalog()));

        $result = $useCase($this->commandForMobile('50.00', MobileUsageBand::HIGH));

        self::assertSame(Decision::SWITCH->value, $result->decision);
        self::assertNotNull($result->suggestedOffer);
        self::assertNotNull($result->estimatedImpactSummary);
    }

    public function test_it_returns_wait_for_active_commitment_using_real_evaluator(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::clearSavingsCatalog()));

        $result = $useCase(
            $this->commandForMobile(
                '50.00',
                MobileUsageBand::HIGH,
                commitmentStatus: CommitmentStatus::YES,
                commitmentEnd: new ApproximateDate(2026, 12),
            ),
        );

        self::assertSame(Decision::WAIT->value, $result->decision);
        self::assertSame('TIMING', $result->waitKind);
        self::assertSame('COMMITMENT_END', $result->reviewTrigger);
    }

    public function test_it_returns_wait_for_insufficient_minimum_using_real_evaluator(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::clearSavingsCatalog()));

        $result = $useCase($this->commandForMobile('50.00', null));

        self::assertSame(Decision::WAIT->value, $result->decision);
        self::assertSame('WAIT_DUE_TO_UNCERTAINTY', $result->reasonCode);
        self::assertSame('UNCERTAINTY_OR_MISSING_INFO', $result->waitKind);
        self::assertSame('CHECK_MISSING_INFORMATION', $result->reviewTrigger);
    }

    public function test_it_does_not_query_catalog_when_minimum_input_is_not_met(): void
    {
        $catalogPort = new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::clearSavingsCatalog());
        $useCase = $this->useCaseWithRealEvaluator($catalogPort);

        $useCase($this->commandForMobile('50.00', null));

        self::assertSame(0, $catalogPort->calls());
    }

    public function test_it_returns_stay_using_real_evaluator_and_published_catalog(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::noClearImprovementCatalog()));

        $result = $useCase($this->commandForMobile('50.00', MobileUsageBand::HIGH));

        self::assertSame(Decision::STAY->value, $result->decision);
        self::assertNull($result->suggestedOffer);
        self::assertSame('NO_CLEAR_IMPROVEMENT', $result->reasonCode);
    }

    public function test_it_adds_multi_residence_limitation_using_real_evaluator(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::clearSavingsCatalog()));

        $result = $useCase($this->commandForMobile('50.00', MobileUsageBand::HIGH, multipleResidencesDetected: true));

        self::assertContains('MULTI_RESIDENCE_NOT_SUPPORTED', $result->analysisLimitations);
    }

    public function test_it_does_not_select_asymmetric_offer_as_high_fit_automatically(): void
    {
        $useCase = $this->useCaseWithRealEvaluator(new FakePublishedCatalogPort(Gate1PublishedCatalogFixture::asymmetricOfferCatalog()));

        $result = $useCase($this->commandForFiberMobile('70.00'));

        if ($result->decision === Decision::SWITCH->value) {
            self::assertNotNull($result->suggestedOffer);
            self::assertNotSame('Telco Asymmetric', $result->suggestedOffer['provider']);

            return;
        }

        self::assertContains($result->decision, [Decision::WAIT->value, Decision::STAY->value]);
    }

    public function test_it_propagates_evaluation_port_error_for_wiring_only(): void
    {
        // This test checks wiring/error propagation, not final decision behavior.
        $useCase = new EvaluateAssessment(
            new class () implements AssessmentEvaluationPort {
                public function evaluate(\App\Advisor\Domain\Assessment\AssessmentInputSnapshot $snapshot, ?\App\Advisor\Application\Port\PublishedCatalogForEvaluation $catalog): \App\Advisor\Domain\Assessment\AssessmentResult
                {
                    throw new RuntimeException('evaluation failed');
                }
            },
            new FakePublishedCatalogPort(),
            new InMemoryAssessmentRepository(),
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator(Uuid::fromString(self::ASSESSMENT_UUID)),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('evaluation failed');

        $useCase($this->commandForMobile('50.00', MobileUsageBand::HIGH));
    }

    private function useCaseWithRealEvaluator(FakePublishedCatalogPort $catalogPort): EvaluateAssessment
    {
        return new EvaluateAssessment(
            $this->realEvaluationPort(),
            $catalogPort,
            new InMemoryAssessmentRepository(),
            new FixedClock(new DateTimeImmutable('2026-01-01 10:00:00')),
            new FixedIdGenerator(Uuid::fromString(self::ASSESSMENT_UUID)),
        );
    }

    private function realEvaluationPort(): AssessmentEvaluator
    {
        return new AssessmentEvaluator(
            new MinimumEvaluableInputRule(),
            new PublishedOfferEvaluationAssembler(),
            new FitCalculator(),
            new EstimatedImpactCalculator(),
            new ChangeFrictionCalculator(),
            new AlternativeSelector(),
            new SwitchRecommendationBuilder(),
            new WaitRecommendationBuilder(),
            new StayRecommendationBuilder(),
            new EvaluationTraceBuilder(),
        );
    }

    private function commandForMobile(
        string $price,
        ?MobileUsageBand $usageBand,
        CommitmentStatus $commitmentStatus = CommitmentStatus::NO,
        ?ApproximateDate $commitmentEnd = null,
        bool $multipleResidencesDetected = false,
    ): EvaluateAssessmentCommand {
        return new EvaluateAssessmentCommand(
            currentProvider: 'Provider A',
            productType: ProductType::MOBILE->value,
            approxMonthlyPriceAmount: $price,
            approxMonthlyPriceCurrency: 'EUR',
            mobileLinesCount: 1,
            mobileUsageBand: $usageBand?->value,
            fiberNeedBand: null,
            commitmentStatus: $commitmentStatus->value,
            commitmentEndYear: $commitmentEnd?->year(),
            commitmentEndMonth: $commitmentEnd?->month(),
            promotionStatus: 'NOT_ACTIVE',
            promotionEndYear: null,
            promotionEndMonth: null,
            tvIncluded: false,
            multipleResidencesDetected: $multipleResidencesDetected,
            dataProvenance: 'DECLARED_BY_USER',
            userPreference: 'BALANCE',
            additionalConditionProfile: null,
            captureChannel: 'WEB',
            captureExperienceMode: 'GUIDED',
        );
    }

    private function commandForFiberMobile(string $price): EvaluateAssessmentCommand
    {
        return new EvaluateAssessmentCommand(
            currentProvider: 'Provider A',
            productType: ProductType::FIBER_MOBILE->value,
            approxMonthlyPriceAmount: $price,
            approxMonthlyPriceCurrency: 'EUR',
            mobileLinesCount: 2,
            mobileUsageBand: MobileUsageBand::HIGH->value,
            fiberNeedBand: FiberNeedBand::STANDARD->value,
            commitmentStatus: CommitmentStatus::NO->value,
            commitmentEndYear: null,
            commitmentEndMonth: null,
            promotionStatus: 'NOT_ACTIVE',
            promotionEndYear: null,
            promotionEndMonth: null,
            tvIncluded: false,
            multipleResidencesDetected: false,
            dataProvenance: 'DECLARED_BY_USER',
            userPreference: 'BALANCE',
            additionalConditionProfile: null,
            captureChannel: 'WEB',
            captureExperienceMode: 'GUIDED',
        );
    }
}
