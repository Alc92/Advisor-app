<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\ViewModel;

use App\Advisor\Application\ViewModel\AssessmentResultViewModelMapper;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\AssessmentResult;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\EstimatedImpact;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Assessment\Recommendation;
use App\Advisor\Domain\Assessment\RecommendedOfferSnapshot;
use App\Advisor\Domain\Enum\AnalysisLimitationCode;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\Decision;
use App\Advisor\Domain\Enum\DecisionReasonCode;
use App\Advisor\Domain\Enum\ImpactType;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\ReviewTrigger;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\Enum\WaitKind;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AssessmentResultViewModelMapperTest extends TestCase
{
    public function test_it_maps_complete_switch_result_with_suggested_offer(): void
    {
        $viewModel = $this->mapper()->map(
            $this->assessment(),
            $this->buildResult(
                new Recommendation(
                    Decision::SWITCH,
                    DecisionReasonCode::CLEAR_SAVINGS,
                    null,
                    Uuid::fromString('22222222-2222-2222-2222-222222222222'),
                    new RecommendedOfferSnapshot('Provider B', 'Plan B', new Money('29.99', 'EUR'), 2, true, 600, '50GB'),
                    new EstimatedImpact(new Money('20.00', 'EUR'), null, ImpactType::MONTHLY_SAVINGS, 'Ahorro mensual claro.'),
                    'Cambia para ahorrar.',
                    ['Sin TV premium'],
                    ['Cambio de permanencia'],
                    null,
                    [AnalysisLimitationCode::SIMPLIFIED_MODEL_LIMITATION],
                    null,
                    null,
                ),
            ),
        );

        self::assertSame('SWITCH', $viewModel->decision);
        self::assertSame('CLEAR_SAVINGS', $viewModel->reasonCode);
        self::assertSame('Te compensa cambiar', $viewModel->headline);
        self::assertSame('Ahorro mensual claro.', $viewModel->estimatedImpactSummary);
        self::assertNotNull($viewModel->suggestedOffer);
        self::assertSame('Provider B', $viewModel->suggestedOffer['provider']);
        self::assertSame('Plan B', $viewModel->suggestedOffer['commercialName']);
        self::assertSame('29.99', $viewModel->suggestedOffer['monthlyPriceAmount']);
        self::assertSame('EUR', $viewModel->suggestedOffer['monthlyPriceCurrency']);
        self::assertSame(600, $viewModel->suggestedOffer['fiberSpeedMbps']);
        self::assertSame('50GB', $viewModel->suggestedOffer['mobileDataDisplay']);
        self::assertSame(2, $viewModel->suggestedOffer['mobileLinesIncluded']);
        self::assertTrue($viewModel->suggestedOffer['tvIncluded']);
    }

    public function test_it_maps_wait_result_with_wait_kind_review_trigger_and_review_moment(): void
    {
        $viewModel = $this->mapper()->map(
            $this->assessment(),
            $this->buildResult(
                new Recommendation(
                    Decision::WAIT,
                    DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
                    WaitKind::UNCERTAINTY_OR_MISSING_INFO,
                    null,
                    null,
                    new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Falta confirmar datos.'),
                    'Es mejor esperar.',
                    [],
                    [],
                    'Hay incertidumbre.',
                    [],
                    new ApproximateDate(2026, 9),
                    ReviewTrigger::CHECK_MISSING_INFORMATION,
                ),
            ),
        );

        self::assertSame('WAIT', $viewModel->decision);
        self::assertSame('WAIT_DUE_TO_UNCERTAINTY', $viewModel->reasonCode);
        self::assertSame('Te conviene esperar', $viewModel->headline);
        self::assertNull($viewModel->suggestedOffer);
        self::assertSame('UNCERTAINTY_OR_MISSING_INFO', $viewModel->waitKind);
        self::assertSame('CHECK_MISSING_INFORMATION', $viewModel->reviewTrigger);
        self::assertSame(['year' => 2026, 'month' => 9], $viewModel->recommendedReviewMoment);
    }

    public function test_it_maps_stay_result_without_suggested_offer(): void
    {
        $viewModel = $this->mapper()->map(
            $this->assessment(),
            $this->buildResult(
                new Recommendation(
                    Decision::STAY,
                    DecisionReasonCode::NO_CLEAR_IMPROVEMENT,
                    null,
                    null,
                    null,
                    new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'No hay mejora.'),
                    'Mantener situacion actual.',
                    [],
                    [],
                    null,
                    [],
                    null,
                    null,
                ),
            ),
        );

        self::assertSame('STAY', $viewModel->decision);
        self::assertSame('No compensa cambiar ahora', $viewModel->headline);
        self::assertNull($viewModel->suggestedOffer);
        self::assertNull($viewModel->waitKind);
        self::assertNull($viewModel->reviewTrigger);
    }

    public function test_it_preserves_analysis_limitations_trade_offs_risks_and_uncertainty(): void
    {
        $viewModel = $this->mapper()->map(
            $this->assessment(),
            $this->buildResult(
                new Recommendation(
                    Decision::WAIT,
                    DecisionReasonCode::WAIT_DUE_TO_UNCERTAINTY,
                    WaitKind::UNCERTAINTY_OR_MISSING_INFO,
                    null,
                    null,
                    new EstimatedImpact(null, null, ImpactType::NO_CLEAR_IMPACT, 'Impacto no claro.'),
                    'Revisar luego.',
                    ['Pierdes velocidad'],
                    ['Riesgo de corte'],
                    'Incertidumbre alta.',
                    [AnalysisLimitationCode::MULTI_RESIDENCE_NOT_SUPPORTED, AnalysisLimitationCode::SIMPLIFIED_MODEL_LIMITATION],
                    null,
                    ReviewTrigger::USER_REQUESTED_REVIEW,
                ),
            ),
        );

        self::assertSame(['Pierdes velocidad'], $viewModel->tradeOffs);
        self::assertSame(['Riesgo de corte'], $viewModel->risks);
        self::assertSame('Incertidumbre alta.', $viewModel->uncertaintySummary);
        self::assertSame(['MULTI_RESIDENCE_NOT_SUPPORTED', 'SIMPLIFIED_MODEL_LIMITATION'], $viewModel->analysisLimitations);
    }

    public function test_it_outputs_scalars_and_arrays_without_domain_objects(): void
    {
        $viewModel = $this->mapper()->map(
            $this->assessment(),
            $this->buildResult(
                new Recommendation(
                    Decision::SWITCH,
                    DecisionReasonCode::CLEAR_SAVINGS,
                    null,
                    Uuid::fromString('33333333-3333-3333-3333-333333333333'),
                    new RecommendedOfferSnapshot('Provider C', 'Plan C', new Money('35.00', 'EUR'), 1, false, null, null),
                    new EstimatedImpact(new Money('10.00', 'EUR'), null, ImpactType::MONTHLY_SAVINGS, 'Ahorro estimado.'),
                    'Cambio recomendado.',
                    ['Sin TV'],
                    ['Ninguno'],
                    null,
                    [AnalysisLimitationCode::MISSING_CRITICAL_DATA],
                    null,
                    null,
                ),
            ),
        );

        self::assertIsString($viewModel->assessmentId);
        self::assertIsString($viewModel->decision);
        self::assertIsString($viewModel->reasonCode);
        self::assertIsString($viewModel->headline);
        self::assertIsString($viewModel->mainExplanation);
        self::assertIsString($viewModel->estimatedImpactSummary);
        self::assertIsArray($viewModel->suggestedOffer);
        self::assertIsArray($viewModel->tradeOffs);
        self::assertIsArray($viewModel->risks);
        self::assertIsArray($viewModel->analysisLimitations);
        self::assertNoObjects($viewModel->suggestedOffer);
        self::assertNoObjects($viewModel->tradeOffs);
        self::assertNoObjects($viewModel->risks);
        self::assertNoObjects($viewModel->analysisLimitations);
    }

    private function mapper(): AssessmentResultViewModelMapper
    {
        return new AssessmentResultViewModelMapper();
    }

    private function assessment(): Assessment
    {
        $currentSituation = new CurrentSituation(
            'Provider A',
            ProductType::MOBILE,
            new Money('49.99', 'EUR'),
            1,
            MobileUsageBand::HIGH,
            null,
            CommitmentStatus::NO,
            null,
            PromotionStatus::NOT_ACTIVE,
            null,
            true,
            false,
            DataProvenance::DECLARED_BY_USER,
        );

        $snapshot = new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::BALANCE,
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );

        return Assessment::createEphemeral(
            AssessmentId::fromUuid(Uuid::fromString('11111111-1111-1111-1111-111111111111')),
            $snapshot,
            new DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }

    private function buildResult(Recommendation $recommendation): AssessmentResult
    {
        return new AssessmentResult($recommendation, null, new DateTimeImmutable('2026-01-01 10:01:00'));
    }

    /**
     * @param array<mixed> $data
     */
    private function assertNoObjects(array $data): void
    {
        foreach ($data as $value) {
            self::assertFalse(is_object($value));
            if (is_array($value)) {
                $this->assertNoObjects($value);
            }
        }
    }
}
