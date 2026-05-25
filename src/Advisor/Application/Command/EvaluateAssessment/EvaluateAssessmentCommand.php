<?php

declare(strict_types=1);

namespace App\Advisor\Application\Command\EvaluateAssessment;

use InvalidArgumentException;

final readonly class EvaluateAssessmentCommand
{
    public string $currentProvider;
    public string $productType;
    public string $approxMonthlyPriceAmount;
    public string $approxMonthlyPriceCurrency;
    public ?int $mobileLinesCount;
    public ?string $mobileUsageBand;
    public ?string $fiberNeedBand;
    public string $commitmentStatus;
    public ?int $commitmentEndYear;
    public ?int $commitmentEndMonth;
    public string $promotionStatus;
    public ?int $promotionEndYear;
    public ?int $promotionEndMonth;
    public ?bool $tvIncluded;
    public bool $multipleResidencesDetected;
    public string $dataProvenance;
    public string $userPreference;
    /**
     * @var array<string, mixed>|null
     */
    public ?array $additionalConditionProfile;
    public string $captureChannel;
    public string $captureExperienceMode;

    /**
     * @param array<string, mixed>|null $additionalConditionProfile
     */
    public function __construct(
        string $currentProvider,
        string $productType,
        string $approxMonthlyPriceAmount,
        string $approxMonthlyPriceCurrency,
        ?int $mobileLinesCount,
        ?string $mobileUsageBand,
        ?string $fiberNeedBand,
        string $commitmentStatus,
        ?int $commitmentEndYear,
        ?int $commitmentEndMonth,
        string $promotionStatus,
        ?int $promotionEndYear,
        ?int $promotionEndMonth,
        ?bool $tvIncluded,
        bool $multipleResidencesDetected,
        string $dataProvenance,
        string $userPreference,
        ?array $additionalConditionProfile,
        string $captureChannel,
        string $captureExperienceMode,
    ) {
        $this->currentProvider = $this->requireNonEmpty($currentProvider, 'currentProvider');
        $this->productType = $this->requireNonEmpty($productType, 'productType');
        $this->approxMonthlyPriceAmount = $this->requireNonEmpty($approxMonthlyPriceAmount, 'approxMonthlyPriceAmount');
        $this->approxMonthlyPriceCurrency = $this->requireNonEmpty($approxMonthlyPriceCurrency, 'approxMonthlyPriceCurrency');
        $this->commitmentStatus = $this->requireNonEmpty($commitmentStatus, 'commitmentStatus');
        $this->promotionStatus = $this->requireNonEmpty($promotionStatus, 'promotionStatus');
        $this->dataProvenance = $this->requireNonEmpty($dataProvenance, 'dataProvenance');
        $this->userPreference = $this->requireNonEmpty($userPreference, 'userPreference');
        $this->captureChannel = $this->requireNonEmpty($captureChannel, 'captureChannel');
        $this->captureExperienceMode = $this->requireNonEmpty($captureExperienceMode, 'captureExperienceMode');

        $this->mobileLinesCount = $mobileLinesCount;
        $this->mobileUsageBand = $mobileUsageBand;
        $this->fiberNeedBand = $fiberNeedBand;
        $this->commitmentEndYear = $commitmentEndYear;
        $this->commitmentEndMonth = $commitmentEndMonth;
        $this->promotionEndYear = $promotionEndYear;
        $this->promotionEndMonth = $promotionEndMonth;
        $this->tvIncluded = $tvIncluded;
        $this->multipleResidencesDetected = $multipleResidencesDetected;

        if ($this->mobileLinesCount !== null && $this->mobileLinesCount < 1) {
            throw new InvalidArgumentException('mobileLinesCount must be at least 1 when provided.');
        }

        $this->validateYearMonthPair($this->commitmentEndYear, $this->commitmentEndMonth, 'commitmentEnd');
        $this->validateYearMonthPair($this->promotionEndYear, $this->promotionEndMonth, 'promotionEnd');

        $this->additionalConditionProfile = $additionalConditionProfile;
    }

    private function requireNonEmpty(string $value, string $field): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException($field . ' cannot be empty.');
        }

        return $trimmed;
    }

    private function validateYearMonthPair(?int $year, ?int $month, string $field): void
    {
        if (($year === null) !== ($month === null)) {
            throw new InvalidArgumentException($field . ' year/month must be provided together.');
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new InvalidArgumentException($field . ' month must be between 1 and 12.');
        }
    }
}
