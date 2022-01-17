<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Module as OscPayPalModule;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;

/**
 * Testing \OxidSolutionCatalysts\PayPal\Core\ViewConfig class.
 */
final class ViewConfigTest extends UnitTestCase
{
    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        \OxidEsales\Eshop\Core\DatabaseProvider::getDB()->execute("delete from oxpayments where OXID = 'oxidpaypal' ");

        parent::tearDown();
    }

    /**
     * PSPAYPAL-491 -->
     * Banner feature enabled? test
     */
    public function testShowPayPalBannerOnStartPage(): void
    {
        $this->updateModuleSetting('oePayPalBannersShowAll', false);

        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);
        $this->assertFalse($view->enablePayPalBanners());

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->assertTrue($view->enablePayPalBanners());
    }

    /**
     * Test case for ViewConfig::getPayPalClientId()
     * @Todo needs new test with Core\Config mock
     */
    public function testGetPayPalClientIdId(): void
    {
        $this->markTestIncomplete('TODO');
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnStartPage()
     */
    public function testShowBannersStartPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting('oePayPalBannersStartPage', true);
        $this->assertTrue($view->showPayPalBannerOnStartPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnStartPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnCategoryPage()
     */
    public function testShowPayPalBannerOnCategoryPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting('oePayPalBannersCategoryPage', true);
        $this->assertTrue($view->showPayPalBannerOnCategoryPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnCategoryPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnSearchResultsPage()
     */
    public function testShowPayPalBannerOnSearchResultsPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting('oePayPalBannersSearchResultsPage', true);
        $this->assertTrue($view->showPayPalBannerOnSearchResultsPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnSearchResultsPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnProductDetailsPage()
     */
    public function testShowPayPalBannerOnProductDetailsPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting('oePayPalBannersProductDetailsPage', true);
        $this->assertTrue($view->showPayPalBannerOnProductDetailsPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnProductDetailsPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnCheckoutPage()
     *
     * @dataProvider providerBannerCheckoutPage
     *
     * @param string $actionClassName
     * @param string $selectorSetting
     */
    public function showPayPalBannerOnCheckoutPage(string $actionClassName, string $selectorSetting): void
    {
        $viewMock = $this
            ->getMockBuilder(\OxidSolutionCatalysts\PayPal\Core\ViewConfig::class)
            ->setMethods(['getActionClassName'])
            ->getMock();
        $viewMock->expects($this->once())->method('getActionClassName')->will($this->returnValue($actionClassName));

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting('oePayPalBannersCheckoutPage', true);
        $this->assertTrue($viewMock->showPayPalBannerOnCheckoutPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', false);
        $this->assertFalse($viewMock->showPayPalBannerOnCheckoutPage());

        $this->updateModuleSetting('oePayPalBannersShowAll', true);
        $this->updateModuleSetting($selectorSetting, '');
        $this->assertFalse($viewMock->showPayPalBannerOnCheckoutPage());
    }

    public function providerBannerCheckoutPage(): array
    {
        return [
            ['basket', 'oePayPalBannersCartPageSelector'],
            ['payment', 'oePayPalBannersPaymentPageSelector']
        ];
    }

    /**
     * Test case for ViewConfig::getPayPalBannersColorScheme()
     *
     * @dataProvider providerGetPayPalColorScheme
     */
    public function testPayPalBannerColorScheme($colorScheme): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->updateModuleSetting('oePayPalBannersColorScheme', $colorScheme);
        $this->assertEquals($colorScheme, $view->getPayPalBannersColorScheme());
    }

    public function providerGetPayPalColorScheme(): array
    {
        return [
            ['blue'],
            ['black'],
            ['white'],
            ['white-no-border'],
        ];
    }
    // <-- PSPAYPAL-491

    /**
     * @param mixed $value
     */
    private function updateModuleSetting(string $name, $value): void
    {
        $moduleSettingsBridge = ContainerFactory::getInstance()->getContainer()->get(ModuleSettingBridgeInterface::class);
        $moduleSettingsBridge->save($name, $value, OscPayPalModule::MODULE_ID);
    }
}
