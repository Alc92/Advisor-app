<?php

declare(strict_types=1);

namespace App\Advisor\Application\UseCase\EvaluateAssessment;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\AssessmentRepository;
use App\Advisor\Application\Port\PublishedCatalogPort;
use App\Advisor\Application\ViewModel\AssessmentResultViewModel;
use App\Advisor\Domain\Assessment\AdditionalConditionProfile;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\FiberSpeedBandCurrent;
use App\Advisor\Domain\Enum\MobileDataAdequacyLevel;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\MobileUsageDistribution;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
use App\Advisor\Domain\Enum\TriState;
use App\Advisor\Domain\Enum\UserPreference;
use App\Advisor\Domain\ValueObject\ApproximateDate;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\Money;
use App\Shared\Application\Port\Clock;
use App\Shared\Application\Port\IdGenerator;

final readonly class EvaluateAssessment
{
    public function __construct(
        private AssessmentEvaluationPort $assessmentEvaluation,
        private PublishedCatalogPort $publishedCatalogs,
        private AssessmentRepository $assessments,
        private Clock $clock,
        private IdGenerator $idGenerator,
    ) {
    }

    public function __invoke(EvaluateAssessmentCommand $command): AssessmentResultViewModel
    {
        $snapshot = $this->buildSnapshot($command);
        $assessmentId = AssessmentId::fromUuid($this->idGenerator->generate());
        $assessment = Assessment::createEphemeral($assessmentId, $snapshot, $this->clock->now());

        $catalog = $snapshot->minimumDataForEvaluationIsMet()
            ? $this->publishedCatalogs->getCurrentPublishedCatalog()
            : null;

        $result = $this->assessmentEvaluation->evaluate($snapshot, $catalog);
        $evaluationMode = $result->evaluationTrace()?->evaluationMode()
            ?? ($snapshot->minimumDataForEvaluationIsMet()
                ? EvaluationMode::EVALUATED_NORMAL
                : EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET);

        $assessment->markAsEvaluated(
            $result,
            $evaluationMode,
            'gate1_assessment_evaluation',
            $catalog?->publicationVersion(),
            $this->clock->now(),
        );

        $this->assessments->save($assessment);

        $decision = $result->recommendation()->decision()->value;
        $headline = match ($decision) {
            'SWITCH' => 'Te conviene cambiar',
            'WAIT' => 'Ahora mismo te conviene esperar',
            'STAY' => 'Te conviene mantener tu tarifa actual',
            default => 'Resultado de evaluación',
        };

        return new AssessmentResultViewModel(
            assessmentId: $assessmentId->toString(),
            decision: $decision,
            reasonCode: $result->recommendation()->reasonCode()->value,
            headline: $headline,
            mainExplanation: $result->recommendation()->mainExplanation(),
            estimatedImpactSummary: null,
            suggestedOffer: null,
            tradeOffs: [],
            risks: [],
            uncertaintySummary: null,
            analysisLimitations: [],
            waitKind: null,
            recommendedReviewMoment: null,
            reviewTrigger: null,
        );
    }

    private function buildSnapshot(EvaluateAssessmentCommand $command): AssessmentInputSnapshot
    {
        $currentSituation = new CurrentSituation(
            $command->currentProvider,
            ProductType::from($command->productType),
            new Money($command->approxMonthlyPriceAmount, $command->approxMonthlyPriceCurrency),
            $command->mobileLinesCount,
            $command->mobileUsageBand !== null ? MobileUsageBand::from($command->mobileUsageBand) : null,
            $command->fiberNeedBand !== null ? FiberNeedBand::from($command->fiberNeedBand) : null,
            CommitmentStatus::from($command->commitmentStatus),
            $this->buildApproximateDate($command->commitmentEndYear, $command->commitmentEndMonth),
            PromotionStatus::from($command->promotionStatus),
            $this->buildApproximateDate($command->promotionEndYear, $command->promotionEndMonth),
            $command->tvIncluded,
            $command->multipleResidencesDetected,
            DataProvenance::from($command->dataProvenance),
        );

        $additionalConditionProfile = $this->buildAdditionalConditionProfile($command->additionalConditionProfile);

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::from($command->userPreference),
            $additionalConditionProfile,
            InputQuality::fromCurrentSituation($currentSituation, $additionalConditionProfile),
        );
    }

    /**
     * @param array<string, mixed>|null $profile
     */
    private function buildAdditionalConditionProfile(?array $profile): ?AdditionalConditionProfile
    {
        if ($profile === null || $profile === []) {
            return null;
        }

        $additionalConditionProfile = new AdditionalConditionProfile(
            $this->enumFromProfile($profile, 'fiberSpeedBandCurrent', FiberSpeedBandCurrent::class),
            $this->enumFromProfile($profile, 'mobileDataAdequacyLevel', MobileDataAdequacyLevel::class),
            $this->enumFromProfile($profile, 'mobileUsageDistribution', MobileUsageDistribution::class),
            $this->enumFromProfile($profile, 'tvImportance', TriState::class),
        );

        return $additionalConditionProfile->isEmpty() ? null : $additionalConditionProfile;
    }

    /**
     * @template T of \BackedEnum
     * @param array<string, mixed> $profile
     * @param class-string<T> $enumClass
     * @return T|null
     */
    private function enumFromProfile(array $profile, string $key, string $enumClass): ?\BackedEnum
    {
        if (!array_key_exists($key, $profile) || $profile[$key] === null || $profile[$key] === '') {
            return null;
        }

        return $enumClass::from($profile[$key]);
    }

    private function buildApproximateDate(?int $year, ?int $month): ?ApproximateDate
    {
        if ($year === null || $month === null) {
            return null;
        }

        return new ApproximateDate($year, $month);
    }
}
