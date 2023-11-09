<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use PHPUnit\Framework\TestCase;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Module as OscPayPalModule;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;

/**
 * Testing \OxidSolutionCatalysts\PayPal\Core\ViewConfig class.
 */
final class ViewConfigTest extends TestCase
{
    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        \OxidEsales\Eshop\Core\DatabaseProvider::getDB()->execute("delete from oxpayments where OXID = 'oscpaypal' ");

        parent::tearDown();
    }

    /**
     * PSPAYPAL-491 -->
     * Banner feature enabled? test
     */
    public function testShowPayPalBannerOnStartPage(): void
    {
        $view = oxNew(ViewConfig::class);

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($view->enablePayPalBanners());

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
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
        $view = oxNew(ViewConfig::class);

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting('oscPayPalBannersStartPage', true);
        $this->assertTrue($view->showPayPalCheckoutBannerOnStartPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalCheckoutBannerOnStartPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnCategoryPage()
     */
    public function testShowPayPalBannerOnCategoryPage(): void
    {
        $view = oxNew(ViewConfig::class);

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting('oscPayPalBannersCategoryPage', true);
        $this->assertTrue($view->showPayPalCheckoutBannerOnCategoryPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalCheckoutBannerOnCategoryPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnSearchResultsPage()
     */
    public function testShowPayPalBannerOnSearchResultsPage(): void
    {
        $view = oxNew(ViewConfig::class);

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting('oscPayPalBannersSearchResultsPage', true);
        $this->assertTrue($view->showPayPalCheckoutBannerOnSearchResultsPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalCheckoutBannerOnSearchResultsPage());
    }

    /**
     * Test case for ViewConfig::showPayPalBannerOnProductDetailsPage()
     */
    public function testShowPayPalBannerOnProductDetailsPage(): void
    {
        $view = oxNew(ViewConfig::class);

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting('oscPayPalBannersProductDetailsPage', true);
        $this->assertTrue($view->showPayPalCheckoutBannerOnProductDetailsPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($view->showPayPalCheckoutBannerOnProductDetailsPage());
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

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting('oscPayPalBannersCheckoutPage', true);
        $this->assertTrue($viewMock->showPayPalCheckoutBannerOnCheckoutPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', false);
        $this->assertFalse($viewMock->showPayPalCheckoutBannerOnCheckoutPage());

        $this->updateModuleSetting('oscPayPalBannersShowAll', true);
        $this->updateModuleSetting($selectorSetting, '');
        $this->assertFalse($viewMock->showPayPalCheckoutBannerOnCheckoutPage());
    }

    public function providerBannerCheckoutPage(): array
    {
        return [
            ['basket', 'oscPayPalBannersCartPageSelector'],
            ['payment', 'oscPayPalBannersPaymentPageSelector']
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

        $this->updateModuleSetting('oscPayPalBannersColorScheme', $colorScheme);
        $this->assertEquals($colorScheme, $view->getPayPalCheckoutBannersColorScheme());
    }

    public function providerGetPayPalColorScheme(): array
    {
        return [
            ['blue'],
            ['black'],
            ['white'],
            ['gray'],
            ['monochrome'],
            ['grayscale'],
        ];
    }
    // <-- PSPAYPAL-491

    /**
     * @param mixed $value
     */
    private function updateModuleSetting(string $name, $value): void
    {
        $moduleSettingsBridge = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettingBridgeInterface::class);
        $moduleSettingsBridge->save($name, $value, OscPayPalModule::MODULE_ID);
    }
}
