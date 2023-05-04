<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception;

use OxidEsales\Codeception\Admin\AdminLoginPage;
use OxidEsales\Codeception\Admin\AdminPanel;
use OxidEsales\Codeception\Page\Home;
use Codeception\Util\Fixtures;
use OxidEsales\Facts\Facts;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use ServiceContainer;

    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /**
     * @param $value mixed
     */
    public function updateModuleConfiguration(string $confName, $value): void
    {
        $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
    }

    /**
     * Open shop first page.
     */
    public function openShop(): Home
    {
        $I = $this;
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);

        return $homePage;
    }

    public function setPayPalBannersVisibility(bool $on = false): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', $on);
        $I->updateModuleConfiguration('oscPayPalBannersStartPage', $on);
        $I->updateModuleConfiguration('oscPayPalBannersCategoryPage', $on);
        $I->updateModuleConfiguration('oscPayPalBannersSearchResultsPage', $on);
        $I->updateModuleConfiguration('oscPayPalBannersProductDetailsPage', $on);
        $I->updateModuleConfiguration('oscPayPalBannersCheckoutPage', $on);
    }

    public function setPayPalBannersFlowSelectors(): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oscPayPalBannersStartPageSelector', '#wrapper .row');
        $I->updateModuleConfiguration('oscPayPalBannersCategoryPageSelector', '.page-header');
        $I->updateModuleConfiguration('oscPayPalBannersSearchResultsPageSelector', '#content .page-header .clearfix');
        $I->updateModuleConfiguration('oscPayPalBannersProductDetailsPageSelector', '#detailsItemsPager');
        $I->updateModuleConfiguration('oscPayPalBannersCartPageSelector', '.cart-buttons');
        $I->updateModuleConfiguration('oscPayPalBannersPaymentPageSelector', '.checkoutSteps ~ .spacer');
    }

    public function setPayPalBannersCustomSelectors(string $selector = ''): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oscPayPalBannersStartPageSelector', $selector);
        $I->updateModuleConfiguration('oscPayPalBannersCategoryPageSelector', $selector);
        $I->updateModuleConfiguration('oscPayPalBannersSearchResultsPageSelector', $selector);
        $I->updateModuleConfiguration('oscPayPalBannersProductDetailsPageSelector', $selector);
        $I->updateModuleConfiguration('oscPayPalBannersCartPageSelector', $selector);
        $I->updateModuleConfiguration('oscPayPalBannersPaymentPageSelector', $selector);
    }

    public function dontSeePayPalInstallmentBanner(): self
    {
        $I = $this;

        $I->waitForPageLoad();
        $I->dontSeeElementInDOM("#paypal-installment-banner-container");
        $I->dontSeeElement("//div[contains(@id, 'paypal-installment-banner-container')]//iframe");

        return $this;
    }

    public function seePayPalInstallmentBanner(): self
    {
        $I = $this;

        $I->waitForElement("//div[contains(@id, 'paypal-installment-banner-container')]//iframe");
        $I->switchToIFrame("//div[contains(@id, 'paypal-installment-banner-container')]//iframe");
        $I->waitForElementVisible("//body[node()]");

        $I->switchToIFrame();

        return $this;
    }

    public function checkInstallmentBannerData(
        float $amount = 0,
        string $ratio = '20x1',
        string $currency = 'EUR'
    ): void {
        $I = $this;

        $onloadMethod = $I->executeJS("return PayPalMessage.toString()");
        $I->assertRegExp($this->prepareMessagePartRegex(sprintf("amount: %s", $amount)), $onloadMethod);
        $I->assertRegExp($this->prepareMessagePartRegex(sprintf("ratio: '%s'", $ratio)), $onloadMethod);
        $I->assertRegExp($this->prepareMessagePartRegex(sprintf("currency: '%s'", $currency)), $onloadMethod);
    }

    /**
     * Wrap the message part in message required conditions
     */
    protected function prepareMessagePartRegex(string $part): string
    {
        return "/paypal.Messages\(\{[^}\)]*{$part}/";
    }

    public function openAdmin(): AdminLoginPage
    {
        $I = $this;
        $adminLogin = new AdminLoginPage($I);
        $I->amOnPage($adminLogin->URL);
        return $adminLogin;
    }

    public function loginAdmin(): AdminPanel
    {
        $adminPage = $this->openAdmin();
        $admin = Fixtures::get('adminUser');
        return $adminPage->login($admin['userLoginName'], $admin['userPassword']);
    }

    public function getShopUrl(): string
    {
        $facts = new Facts();

        return $facts->getShopUrl();
    }

    public function switchToLastWindow()
    {
        $I = $this;
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $last_window = end($handles);
            $webdriver->switchTo()->window($last_window);
            $size = new \Facebook\WebDriver\WebDriverDimension(1920, 1280);
            $webdriver->manage()->window()->setSize($size);
        });
    }
}
