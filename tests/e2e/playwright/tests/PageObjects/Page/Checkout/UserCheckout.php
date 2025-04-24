<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Checkout;

use OxidEsales\Codeception\Page\Component\PaymentSummary;
use OxidEsales\Codeception\Page\Component\Header\Navigation;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\UserForm;
use OxidEsales\Codeception\Page\Page;

class UserCheckout extends Page
{
    use UserForm;
    use Navigation;
    use PaymentSummary;

    public string $URL = '/index.php?lang=1&cl=user';
    public string $breadCrumb = '//div[@class="step step-1 active"]';
    public string $noRegistrationOption = '//form[@id="optionNoRegistration"]/button';
    public string $registrationOption = '//form[@id="optionRegistration"]/button';
    public string $openShipAddressForm = '#showShipAddress';
    public string $openBillingAddressFormButton = '#userChangeAddress';
    public string $orderRemark = '#orderRemark';
    public string $registerUserButton = '//main[@class="content"]//div[@class="row"]/div[2]//button';
    public string $nextStepButton = '#userFormSubmit';
    public string $selectCountry = '//select[@id="delCountrySelect"]';
    public string $editShippingAddress = '//button[contains(@class, "edit-shipping-address")]';

    // unused
    public string $previousStepButton = '';
    public string $deleteShipAddress = '//div[@id="shippingAddress"]/div[%s]/div/div[2]/button[2]';
    public string $selectShipAddress = '//div[@id="shippingAddress"]/div[%s]/div/div[1]/label';
    public string $openShipAddress = '//div[@id="shippingAddress"]/div[%s]/div/div[2]/button[1]';
    public string $shipAddressForm = '#shippingAddressForm';

    public function selectOptionNoRegistration(): self
    {
        $I = $this->user;
        $I->see(Translator::translate('PURCHASE_WITHOUT_REGISTRATION'));
        $I->waitForElement($this->noRegistrationOption);
        $I->retryClick($this->noRegistrationOption);
        return $this;
    }

    public function selectOptionRegisterNewAccount(): self
    {
        $I = $this->user;
        $I->waitForElement($this->registrationOption);
        $I->retryClick($this->registrationOption);
        return $this;
    }

    public function goToNextStep(): PaymentCheckout
    {
        $I = $this->user;
        $I->waitForElementClickable($this->nextStepButton);
        $I->retryClick($this->nextStepButton);
        $paymentPage = new PaymentCheckout($I);
        $I->waitForElement($paymentPage->breadCrumb);
        return $paymentPage;
    }

    public function goToPreviousStep(): Basket
    {
        $I = $this->user;
        $I->click(Translator::translate('PREVIOUS_STEP'));
        $I->waitForElement($this->breadCrumb);
        return new Basket($I);
    }

    public function clickOnRegisterUserButton(): self
    {
        $I = $this->user;
        $I->click($this->registerUserButton);
        $I->waitForPageLoad();
        $I->waitForElement($this->breadCrumb);
        return $this;
    }

    public function openShippingAddressForm(): self
    {
        $I = $this->user;
        $I->retryClick($this->openShipAddressForm);
        $I->dontSeeCheckboxIsChecked($this->openShipAddressForm);
        return $this;
    }

    public function openUserBillingAddressForm(): self
    {
        $I = $this->user;
        $I->click($this->openBillingAddressFormButton);
        $I->waitForElementVisible($this->billCountryId);
        return $this;
    }

    public function enterOrderRemark(string $orderRemark): self
    {
        $I = $this->user;
        $I->fillField($this->orderRemark, $orderRemark);
        return $this;
    }

    public function selectCountry(string $country): self
    {
        $I = $this->user;
        $I->scrollTo($this->editShippingAddress);
        $I->waitForElementVisible($this->editShippingAddress);
        $I->waitForElementClickable($this->editShippingAddress);
        $I->click($this->editShippingAddress);
        $I->waitForElementVisible($this->selectCountry);
        $I->waitForElementClickable($this->selectCountry);
        $I->selectOption($this->selectCountry, $country);
        $I->seeInField($this->selectCountry, $country);
        return $this;
    }
}
