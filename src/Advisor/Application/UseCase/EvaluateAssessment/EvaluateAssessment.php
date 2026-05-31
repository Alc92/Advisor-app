<?php

declare(strict_types=1);

namespace App\Advisor\Application\UseCase\EvaluateAssessment;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use App\Advisor\Application\Port\AssessmentEvaluationPort;
use App\Advisor\Application\Port\AssessmentRepository;
use App\Advisor\Application\ViewModel\AssessmentResultViewModel;
use App\Advisor\Domain\Assessment\Assessment;
use App\Advisor\Domain\Assessment\AssessmentInputSnapshot;
use App\Advisor\Domain\Assessment\CurrentSituation;
use App\Advisor\Domain\Assessment\InputQuality;
use App\Advisor\Domain\Enum\CommitmentStatus;
use App\Advisor\Domain\Enum\DataProvenance;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\FiberNeedBand;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\Enum\PromotionStatus;
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

        $result = $this->assessmentEvaluation->evaluate($snapshot, null);

        $assessment->markAsEvaluated(
            $result,
            EvaluationMode::EVALUATED_NORMAL,
            'MVP5.1_SKELETON',
            null,
            $this->clock->now(),
        );

        $this->assessments->save($assessment);

        $decision = $result->recommendation()->decision()->value;
        $headline = match ($decision) {
            'SWITCH' => 'Te conviene cambiar',
            'WAIT' => 'Ahora mismo te conviene esperar',
            'STAY' => 'Te conviene mantener tu tarifa actual',
            default => 'Resultado de evaluacion',
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

        return new AssessmentInputSnapshot(
            $currentSituation,
            UserPreference::from($command->userPreference),
            null,
            InputQuality::fromCurrentSituation($currentSituation),
        );
    }

    private function buildApproximateDate(?int $year, ?int $month): ?ApproximateDate
    {
        if ($year === null || $month === null) {
            return null;
        }

        return new ApproximateDate($year, $month);
    }
}
