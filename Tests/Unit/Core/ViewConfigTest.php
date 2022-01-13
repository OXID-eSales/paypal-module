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
        $moduleSettingsBridge = ContainerFactory::getInstance()->getContainer()->get(ModuleSettingBridgeInterface::class);
        $moduleSettingsBridge->save('oePayPalBannersShowAll', false, OscPayPalModule::MODULE_ID);

        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);
        $this->assertFalse($view->enablePayPalBanners());

        $moduleSettingsBridge->save('oePayPalBannersShowAll', true, OscPayPalModule::MODULE_ID);
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

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam('oePayPalBannersStartPage', true);
        $this->assertTrue($view->showPayPalBannerOnStartPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnStartPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnCategoryPage()
     */
    public function testShowPayPalBannerOnCategoryPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam('oePayPalBannersCategoryPage', true);
        $this->assertTrue($view->showPayPalBannerOnCategoryPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnCategoryPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnSearchResultsPage()
     */
    public function testShowPayPalBannerOnSearchResultsPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam('oePayPalBannersSearchResultsPage', true);
        $this->assertTrue($view->showPayPalBannerOnSearchResultsPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalBannerOnSearchResultsPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnProductDetailsPage()
     */
    public function testShowPayPalBannerOnProductDetailsPage(): void
    {
        $view = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam('oePayPalBannersProductDetailsPage', true);
        $this->assertTrue($view->showPayPalBannerOnProductDetailsPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', false);
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

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam('oePayPalBannersCheckoutPage', true);
        $this->assertTrue($viewMock->showPayPalBannerOnCheckoutPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', false);
        $this->assertFalse($viewMock->showPayPalBannerOnCheckoutPage());

        $this->getConfig()->setConfigParam('oePayPalBannersShowAll', true);
        $this->getConfig()->setConfigParam($selectorSetting, '');
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

        $this->getConfig()->setConfigParam('oePayPalBannersColorScheme', $colorScheme);
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
}
