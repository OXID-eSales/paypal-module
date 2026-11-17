<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The checkout flow of PayPal Standard (popup or redirect) - an unknown or missing
 * value must keep the popup flow, the behaviour before the setting existed.
 */
final class ModuleSettingsStandardCheckoutFlowTest extends TestCase
{
    /**
     * @dataProvider checkoutFlowProvider
     */
    public function testCheckoutFlow($storedValue, string $expectedFlow, bool $expectedRedirect): void
    {
        $moduleSettings = $this->createModuleSettings(
            static function () use ($storedValue) {
                return $storedValue;
            }
        );

        $this->assertSame($expectedFlow, $moduleSettings->getPayPalStandardCheckoutFlow());
        $this->assertSame($expectedRedirect, $moduleSettings->isPayPalStandardRedirectFlow());
    }

    public static function checkoutFlowProvider(): array
    {
        return [
            'popup' => ['popup', ModuleSettings::STANDARD_CHECKOUT_FLOW_POPUP, false],
            'redirect' => ['redirect', ModuleSettings::STANDARD_CHECKOUT_FLOW_REDIRECT, true],
            'unknown value' => ['somethingElse', ModuleSettings::STANDARD_CHECKOUT_FLOW_POPUP, false],
            'empty value' => ['', ModuleSettings::STANDARD_CHECKOUT_FLOW_POPUP, false],
            'null' => [null, ModuleSettings::STANDARD_CHECKOUT_FLOW_POPUP, false],
        ];
    }

    public function testMissingSettingFallsBackToPopup(): void
    {
        // e.g. a module configuration from before the setting was introduced
        $moduleSettings = $this->createModuleSettings(
            static function () {
                throw new RuntimeException('Setting oscPayPalStandardCheckoutFlow not found');
            }
        );

        $this->assertSame(ModuleSettings::STANDARD_CHECKOUT_FLOW_POPUP, $moduleSettings->getPayPalStandardCheckoutFlow());
        $this->assertFalse($moduleSettings->isPayPalStandardRedirectFlow());
    }

    private function createModuleSettings(callable $settingValue): ModuleSettings
    {
        $bridge = $this->createMock(ModuleSettingBridgeInterface::class);
        $bridge->method('get')
            ->with('oscPayPalStandardCheckoutFlow', 'osc_paypal')
            ->willReturnCallback($settingValue);

        return new ModuleSettings(
            $bridge,
            $this->createMock(ContextInterface::class),
            $this->createMock(ModuleConfigurationDaoBridgeInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(UserRepository::class)
        );
    }
}
