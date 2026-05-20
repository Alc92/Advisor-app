<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\UserPreference;

final readonly class AssessmentInputSnapshot
{
    public function __construct(
        private CurrentSituation $currentSituation,
        private UserPreference $userPreference,
        private ?AdditionalConditionProfile $additionalConditionProfile,
        private InputQuality $inputQuality,
    ) {
    }

    public function currentSituation(): CurrentSituation
    {
        return $this->currentSituation;
    }

    public function userPreference(): UserPreference
    {
        return $this->userPreference;
    }

    public function additionalConditionProfile(): ?AdditionalConditionProfile
    {
        return $this->additionalConditionProfile;
    }

    public function inputQuality(): InputQuality
    {
        return $this->inputQuality;
    }

    public function minimumDataForEvaluationIsMet(): bool
    {
        return $this->inputQuality->allowsCatalogEvaluation();
    }

    public function hasAdditionalConditionProfile(): bool
    {
        return $this->additionalConditionProfile !== null
            && !$this->additionalConditionProfile->isEmpty();
    }
}
