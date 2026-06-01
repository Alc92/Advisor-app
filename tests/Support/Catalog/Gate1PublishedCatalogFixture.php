<?php

declare(strict_types=1);

namespace App\Tests\Support\Catalog;

use App\Advisor\Application\Port\PublishedCatalogForEvaluation;
use App\Advisor\Application\Port\PublishedOfferVersionForEvaluation;
use App\Advisor\Domain\Enum\MobileUsageBand;
use App\Advisor\Domain\Enum\ProductType;
use App\Advisor\Domain\ValueObject\Money;
use App\Catalog\Domain\Enum\FiberCapacityBand;

final class Gate1PublishedCatalogFixture
{
    public static function publishedCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catpub_gate1_001',
            'gate1-fixture-2026-05-31',
            [
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_mobile_basic_v1',
                    'Telco A',
                    'Móvil Básico 25GB',
                    new Money('12.00', 'EUR'),
                    1,
                    false,
                    null,
                    '25 GB',
                    ProductType::MOBILE,
                    null,
                    MobileUsageBand::MEDIUM,
                    false,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_mobile_unlimited_v1',
                    'Telco B',
                    'Móvil Ilimitado',
                    new Money('20.00', 'EUR'),
                    1,
                    false,
                    null,
                    'ilimitados',
                    ProductType::MOBILE,
                    null,
                    MobileUsageBand::HIGH,
                    false,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_fiber_300_v1',
                    'Telco C',
                    'Fibra 300',
                    new Money('25.00', 'EUR'),
                    0,
                    false,
                    300,
                    null,
                    ProductType::FIBER,
                    FiberCapacityBand::STANDARD,
                    null,
                    true,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_fiber_600_tv_v1',
                    'Telco D',
                    'Fibra 600 + TV',
                    new Money('39.00', 'EUR'),
                    0,
                    true,
                    600,
                    null,
                    ProductType::FIBER,
                    FiberCapacityBand::HIGH,
                    null,
                    true,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_bundle_300_1line_v1',
                    'Telco E',
                    'Fibra 300 + Móvil 50GB',
                    new Money('35.00', 'EUR'),
                    1,
                    false,
                    300,
                    '50 GB',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::STANDARD,
                    MobileUsageBand::MEDIUM,
                    true,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_bundle_600_2lines_tv_v1',
                    'Telco F',
                    'Fibra 600 + 2 líneas + TV',
                    new Money('49.00', 'EUR'),
                    2,
                    true,
                    600,
                    '100 GB compartidos',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::HIGH,
                    MobileUsageBand::HIGH,
                    true,
                    false,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_bundle_asymmetric_3lines_v1',
                    'Telco G',
                    'Fibra 600 + 3 líneas asimétricas',
                    new Money('45.00', 'EUR'),
                    3,
                    false,
                    600,
                    '50GB + 10GB + 10GB',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::HIGH,
                    MobileUsageBand::MEDIUM,
                    true,
                    true,
                ),
            ],
        );
    }

    public static function clearSavingsCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catpub_gate1_clear_savings_001',
            'gate1-clear-savings',
            [
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_clear_savings_mobile_v1',
                    'Telco Savings',
                    'Móvil Ahorro 100GB',
                    new Money('30.00', 'EUR'),
                    1,
                    false,
                    null,
                    '100 GB',
                    ProductType::MOBILE,
                    null,
                    MobileUsageBand::HIGH,
                    false,
                    false,
                ),
            ],
        );
    }

    public static function noClearImprovementCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catpub_gate1_no_improvement_001',
            'gate1-no-improvement',
            [
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_no_improvement_mobile_v1',
                    'Telco Similar',
                    'Móvil Similar 100GB',
                    new Money('48.00', 'EUR'),
                    1,
                    false,
                    null,
                    '100 GB',
                    ProductType::MOBILE,
                    null,
                    MobileUsageBand::HIGH,
                    false,
                    false,
                ),
            ],
        );
    }

    public static function asymmetricOfferCatalog(): PublishedCatalogForEvaluation
    {
        return new PublishedCatalogForEvaluation(
            'catpub_gate1_asymmetric_001',
            'gate1-asymmetric',
            [
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_asym_bundle_v1',
                    'Telco Asymmetric',
                    'Fibra 600 + 3 lineas asimetricas',
                    new Money('40.00', 'EUR'),
                    3,
                    false,
                    600,
                    '50GB + 10GB + 10GB',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::HIGH,
                    MobileUsageBand::HIGH,
                    true,
                    true,
                ),
                new PublishedOfferVersionForEvaluation(
                    'ofv_gate1_sym_bundle_v1',
                    'Telco Symmetric',
                    'Fibra 600 + 2 lineas 100GB',
                    new Money('42.00', 'EUR'),
                    2,
                    false,
                    600,
                    '100 GB',
                    ProductType::FIBER_MOBILE,
                    FiberCapacityBand::HIGH,
                    MobileUsageBand::HIGH,
                    true,
                    false,
                ),
            ],
        );
    }
}
