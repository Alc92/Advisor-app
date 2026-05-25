<?php

declare(strict_types=1);

namespace App\Tests\Unit\Advisor\Application\Command\EvaluateAssessment;

use App\Advisor\Application\Command\EvaluateAssessment\EvaluateAssessmentCommand;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EvaluateAssessmentCommandTest extends TestCase
{
    public function test_evaluate_assessment_command_can_be_created_with_valid_data(): void
    {
        $command = $this->buildCommand();

        self::assertSame('Provider A', $command->currentProvider);
        self::assertSame('FIBER_MOBILE', $command->productType);
        self::assertSame('49.99', $command->approxMonthlyPriceAmount);
        self::assertSame('EUR', $command->approxMonthlyPriceCurrency);
        self::assertSame(2, $command->mobileLinesCount);
        self::assertTrue($command->multipleResidencesDetected);
        self::assertSame(['tv_importance' => 'yes'], $command->additionalConditionProfile);
        self::assertSame('WEB', $command->captureChannel);
        self::assertSame('GUIDED', $command->captureExperienceMode);
    }

    #[DataProvider('emptyCriticalStringFieldDataProvider')]
    public function test_evaluate_assessment_command_rejects_empty_critical_string_fields(string $field): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buildCommand(overrides: [$field => '   ']);
    }

    public static function emptyCriticalStringFieldDataProvider(): iterable
    {
        yield 'currentProvider' => ['currentProvider'];
        yield 'productType' => ['productType'];
        yield 'approxMonthlyPriceAmount' => ['approxMonthlyPriceAmount'];
        yield 'approxMonthlyPriceCurrency' => ['approxMonthlyPriceCurrency'];
        yield 'commitmentStatus' => ['commitmentStatus'];
        yield 'promotionStatus' => ['promotionStatus'];
        yield 'dataProvenance' => ['dataProvenance'];
        yield 'userPreference' => ['userPreference'];
        yield 'captureChannel' => ['captureChannel'];
        yield 'captureExperienceMode' => ['captureExperienceMode'];
    }

    public function test_evaluate_assessment_command_rejects_partial_commitment_end_date(): void
    {
        try {
            $this->buildCommand(overrides: ['commitmentEndYear' => 2026, 'commitmentEndMonth' => null]);
            self::fail('Expected InvalidArgumentException for partial commitment end date.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->buildCommand(overrides: ['commitmentEndYear' => null, 'commitmentEndMonth' => 6]);
    }

    public function test_evaluate_assessment_command_rejects_partial_promotion_end_date(): void
    {
        try {
            $this->buildCommand(overrides: ['promotionEndYear' => 2026, 'promotionEndMonth' => null]);
            self::fail('Expected InvalidArgumentException for partial promotion end date.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->buildCommand(overrides: ['promotionEndYear' => null, 'promotionEndMonth' => 8]);
    }

    #[DataProvider('monthOutOfRangeDataProvider')]
    public function test_evaluate_assessment_command_rejects_month_out_of_range(array $overrides): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buildCommand(overrides: $overrides);
    }

    public static function monthOutOfRangeDataProvider(): iterable
    {
        yield 'commitment month 0' => [['commitmentEndMonth' => 0]];
        yield 'commitment month 13' => [['commitmentEndMonth' => 13]];
        yield 'promotion month 0' => [['promotionEndMonth' => 0]];
        yield 'promotion month 13' => [['promotionEndMonth' => 13]];
    }

    #[DataProvider('invalidMobileLinesCountDataProvider')]
    public function test_evaluate_assessment_command_rejects_invalid_mobile_lines_count(int $mobileLinesCount): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buildCommand(overrides: ['mobileLinesCount' => $mobileLinesCount]);
    }

    public static function invalidMobileLinesCountDataProvider(): iterable
    {
        yield 'zero lines' => [0];
        yield 'negative lines' => [-1];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function buildCommand(array $overrides = []): EvaluateAssessmentCommand
    {
        $data = array_merge([
            'currentProvider' => 'Provider A',
            'productType' => 'FIBER_MOBILE',
            'approxMonthlyPriceAmount' => '49.99',
            'approxMonthlyPriceCurrency' => 'EUR',
            'mobileLinesCount' => 2,
            'mobileUsageBand' => 'HIGH',
            'fiberNeedBand' => 'STANDARD',
            'commitmentStatus' => 'YES',
            'commitmentEndYear' => 2026,
            'commitmentEndMonth' => 6,
            'promotionStatus' => 'ACTIVE',
            'promotionEndYear' => 2026,
            'promotionEndMonth' => 8,
            'tvIncluded' => true,
            'multipleResidencesDetected' => true,
            'dataProvenance' => 'DECLARED_BY_USER',
            'userPreference' => 'BALANCE',
            'additionalConditionProfile' => ['tv_importance' => 'yes'],
            'captureChannel' => 'WEB',
            'captureExperienceMode' => 'GUIDED',
        ], $overrides);

        return new EvaluateAssessmentCommand(
            $data['currentProvider'],
            $data['productType'],
            $data['approxMonthlyPriceAmount'],
            $data['approxMonthlyPriceCurrency'],
            $data['mobileLinesCount'],
            $data['mobileUsageBand'],
            $data['fiberNeedBand'],
            $data['commitmentStatus'],
            $data['commitmentEndYear'],
            $data['commitmentEndMonth'],
            $data['promotionStatus'],
            $data['promotionEndYear'],
            $data['promotionEndMonth'],
            $data['tvIncluded'],
            $data['multipleResidencesDetected'],
            $data['dataProvenance'],
            $data['userPreference'],
            $data['additionalConditionProfile'],
            $data['captureChannel'],
            $data['captureExperienceMode'],
        );
    }
}
