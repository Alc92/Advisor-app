<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\AssessmentStatus;
use App\Advisor\Domain\Enum\EvaluationMode;
use App\Advisor\Domain\Enum\Vertical;
use App\Advisor\Domain\ValueObject\AssessmentId;
use App\Advisor\Domain\ValueObject\UserId;
use DateTimeImmutable;
use InvalidArgumentException;

final class Assessment
{
    private AssessmentId $id;
    private ?UserId $userId;
    private Vertical $vertical;
    private AssessmentStatus $status;
    private AssessmentInputSnapshot $inputSnapshot;
    private EvaluationMode $evaluationMode;
    private ?AssessmentResult $result;
    private ?string $ruleSetVersion;
    private ?string $catalogVersion;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $evaluatedAt;
    private ?DateTimeImmutable $lastReevaluationAt;

    private function __construct(
        AssessmentId $id,
        ?UserId $userId,
        Vertical $vertical,
        AssessmentStatus $status,
        AssessmentInputSnapshot $inputSnapshot,
        EvaluationMode $evaluationMode,
        ?AssessmentResult $result,
        ?string $ruleSetVersion,
        ?string $catalogVersion,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $evaluatedAt,
        ?DateTimeImmutable $lastReevaluationAt,
    ) {
        $this->validateInvariantStatusResult($status, $result, $evaluatedAt, $ruleSetVersion, $catalogVersion, $evaluationMode);

        $this->id = $id;
        $this->userId = $userId;
        $this->vertical = $vertical;
        $this->status = $status;
        $this->inputSnapshot = $inputSnapshot;
        $this->evaluationMode = $evaluationMode;
        $this->result = $result;
        $this->ruleSetVersion = $this->validateRuleSetVersion($ruleSetVersion);
        $this->catalogVersion = $this->validateCatalogVersion($catalogVersion, $evaluationMode);
        $this->createdAt = $createdAt;
        $this->evaluatedAt = $evaluatedAt;
        $this->lastReevaluationAt = $lastReevaluationAt;
    }

    public static function createEphemeral(
        AssessmentId $id,
        AssessmentInputSnapshot $inputSnapshot,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            null,
            Vertical::TELECOM,
            AssessmentStatus::CREATED,
            $inputSnapshot,
            EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET,
            null,
            null,
            null,
            $createdAt,
            null,
            null,
        );
    }

    public function markAsEvaluated(
        AssessmentResult $result,
        EvaluationMode $evaluationMode,
        string $ruleSetVersion,
        ?string $catalogVersion,
        DateTimeImmutable $evaluatedAt,
    ): void {
        $trimmedRuleSet = trim($ruleSetVersion);

        if ($trimmedRuleSet === '') {
            throw new InvalidArgumentException('Rule set version cannot be empty.');
        }

        if ($evaluationMode === EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET && $catalogVersion !== null) {
            throw new InvalidArgumentException('Catalog version must be null when evaluation mode is NOT_EVALUATED_MINIMUM_NOT_MET.');
        }

        $trimmedCatalog = null;

        if ($catalogVersion !== null) {
            $trimmedCatalog = trim($catalogVersion);

            if ($trimmedCatalog === '') {
                throw new InvalidArgumentException('Catalog version cannot be empty.');
            }
        }

        $this->result = $result;
        $this->evaluationMode = $evaluationMode;
        $this->status = AssessmentStatus::EVALUATED;
        $this->ruleSetVersion = $trimmedRuleSet;
        $this->catalogVersion = $trimmedCatalog;
        $this->evaluatedAt = $evaluatedAt;
    }

    public function id(): AssessmentId
    {
        return $this->id;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function vertical(): Vertical
    {
        return $this->vertical;
    }

    public function status(): AssessmentStatus
    {
        return $this->status;
    }

    public function inputSnapshot(): AssessmentInputSnapshot
    {
        return $this->inputSnapshot;
    }

    public function evaluationMode(): EvaluationMode
    {
        return $this->evaluationMode;
    }

    public function result(): ?AssessmentResult
    {
        return $this->result;
    }

    public function ruleSetVersion(): ?string
    {
        return $this->ruleSetVersion;
    }

    public function catalogVersion(): ?string
    {
        return $this->catalogVersion;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function evaluatedAt(): ?DateTimeImmutable
    {
        return $this->evaluatedAt;
    }

    public function lastReevaluationAt(): ?DateTimeImmutable
    {
        return $this->lastReevaluationAt;
    }

    public function isEvaluated(): bool
    {
        return $this->status === AssessmentStatus::EVALUATED;
    }

    public function hasResult(): bool
    {
        return $this->result !== null;
    }

    /**
     * @param-out string|null $ruleSetVersion
     */
    private function validateRuleSetVersion(?string $ruleSetVersion): ?string
    {
        if ($ruleSetVersion === null) {
            return null;
        }

        $trimmed = trim($ruleSetVersion);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Rule set version cannot be empty.');
        }

        return $trimmed;
    }

    /**
     * @param-out string|null $catalogVersion
     */
    private function validateCatalogVersion(?string $catalogVersion, EvaluationMode $evaluationMode): ?string
    {
        if ($evaluationMode === EvaluationMode::NOT_EVALUATED_MINIMUM_NOT_MET && $catalogVersion !== null) {
            throw new InvalidArgumentException('Catalog version must be null when evaluation mode is NOT_EVALUATED_MINIMUM_NOT_MET.');
        }

        if ($catalogVersion === null) {
            return null;
        }

        $trimmed = trim($catalogVersion);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Catalog version cannot be empty.');
        }

        return $trimmed;
    }

    private function validateInvariantStatusResult(
        AssessmentStatus $status,
        ?AssessmentResult $result,
        ?DateTimeImmutable $evaluatedAt,
        ?string $ruleSetVersion,
        ?string $catalogVersion,
        EvaluationMode $evaluationMode,
    ): void {
        if ($status === AssessmentStatus::CREATED) {
            if ($result !== null) {
                throw new InvalidArgumentException('CREATED assessment must have null result.');
            }

            if ($evaluatedAt !== null) {
                throw new InvalidArgumentException('CREATED assessment must have null evaluatedAt.');
            }

            if ($ruleSetVersion !== null) {
                throw new InvalidArgumentException('CREATED assessment must have null ruleSetVersion.');
            }

            if ($catalogVersion !== null) {
                throw new InvalidArgumentException('CREATED assessment must have null catalogVersion.');
            }
        }

        if ($status === AssessmentStatus::EVALUATED) {
            if ($result === null) {
                throw new InvalidArgumentException('EVALUATED assessment must have a result.');
            }

            if ($evaluatedAt === null) {
                throw new InvalidArgumentException('EVALUATED assessment must have evaluatedAt.');
            }

            $trimmedRuleSet = $ruleSetVersion !== null ? trim($ruleSetVersion) : null;

            if ($trimmedRuleSet === null || $trimmedRuleSet === '') {
                throw new InvalidArgumentException('EVALUATED assessment must have a non-empty ruleSetVersion.');
            }
        }
    }
}
