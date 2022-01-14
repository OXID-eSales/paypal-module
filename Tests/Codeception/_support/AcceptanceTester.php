<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception;

use OxidEsales\Codeception\Page\Home;
use Codeception\Util\Fixtures;
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

    public function setPayPalSettingsData(): void
    {
        $I = $this;

        $I->updateModuleConfiguration('blPayPalSandboxMode', true);
        $I->updateModuleConfiguration('sPayPalSandboxClientId', Fixtures::get('sPayPalClientId'));

        /*
        $I->updateConfigInDatabase('blPayPalLoggerEnabled', true, 'bool');
        $I->updateConfigInDatabase('sOEPayPalSandboxUserEmail', Fixtures::get('sOEPayPalSandboxUsername'), 'str');
        $I->updateConfigInDatabase('sOEPayPalSandboxUsername', Fixtures::get('sOEPayPalSandboxUsername'), 'str');
        $I->updateConfigInDatabase('sOEPayPalSandboxPassword', Fixtures::get('sOEPayPalSandboxPassword'), 'str');
        $I->updateConfigInDatabase('sOEPayPalSandboxSignature', Fixtures::get('sOEPayPalSandboxSignature'), 'str');
        */
    }

    public function setPayPalBannersVisibility(bool $on = false): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oePayPalBannersShowAll', $on);
        $I->updateModuleConfiguration('oePayPalBannersStartPage', $on);
        $I->updateModuleConfiguration('oePayPalBannersCategoryPage', $on);
        $I->updateModuleConfiguration('oePayPalBannersSearchResultsPage', $on);
        $I->updateModuleConfiguration('oePayPalBannersProductDetailsPage', $on);
        $I->updateModuleConfiguration('oePayPalBannersCheckoutPage', $on);
    }

    public function setPayPalBannersFlowSelectors(): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oePayPalBannersStartPageSelector', '#wrapper .row');
        $I->updateModuleConfiguration('oePayPalBannersCategoryPageSelector', '.page-header');
        $I->updateModuleConfiguration('oePayPalBannersSearchResultsPageSelector', '#content .page-header .clearfix');
        $I->updateModuleConfiguration('oePayPalBannersProductDetailsPageSelector', '#detailsItemsPager');
        $I->updateModuleConfiguration('oePayPalBannersCartPageSelector', '.cart-buttons');
        $I->updateModuleConfiguration('oePayPalBannersPaymentPageSelector', '.checkoutSteps ~ .spacer');
    }

    public function setPayPalBannersCustomSelectors(string $selector = ''): void
    {
        $I = $this;

        $I->updateModuleConfiguration('oePayPalBannersStartPageSelector', $selector);
        $I->updateModuleConfiguration('oePayPalBannersCategoryPageSelector', $selector);
        $I->updateModuleConfiguration('oePayPalBannersSearchResultsPageSelector', $selector);
        $I->updateModuleConfiguration('oePayPalBannersProductDetailsPageSelector', $selector);
        $I->updateModuleConfiguration('oePayPalBannersCartPageSelector', $selector);
        $I->updateModuleConfiguration('oePayPalBannersPaymentPageSelector', $selector);
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

    public function checkInstallmentBannerData(float $amount = 0, string $ratio = '20x1', string $currency = 'EUR'): void
    {
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
}