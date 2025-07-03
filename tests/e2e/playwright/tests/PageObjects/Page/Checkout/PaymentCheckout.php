<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Checkout;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\Header\Navigation;
use OxidEsales\Codeception\Page\Component\PaymentSummary;
use OxidEsales\Codeception\Page\Page;

class PaymentCheckout extends Page
{
    use Navigation;
    use PaymentSummary;

    public string $URL = 'index.php?lang=1&cl=payment';
    public string $breadCrumb = '//div[@class="step step-2 active"]';
    public string $paymentMethod = '';
    public string $nextStepButton = '//div[@class="row"]/div[2]//button';
    public string $previousStepButton = '';
    public string $selectShippingButton = '//select[@name="sShipSet"]';
    private string $headings = '//h2';
    private string $paymentInformation = '//form[@id="payment"]';
    private string $paymentOption = '//div[@class="payment-option"]';
    private string $noShippingMethodText = 'Currently we have no shipping method set up for this country.';

    public function selectPayment(string $paymentMethod): self
    {
        $I = $this->user;
        $I->click('#payment_' . $paymentMethod);
        return $this;
    }

    public function selectShipping(string $shipping): self
    {
        $I = $this->user;
        $I->selectOption($this->selectShippingButton, $shipping);
        return $this;
    }

    public function selectShippingIsAvailable(): self
    {
        $I = $this->user;
        $I->see(Translator::translate('SELECTED_SHIPPING_CARRIER'), $this->headings);
        $I->seeElement($this->selectShippingButton);
        return $this;
    }

    public function selectShippingIsNotAvailable(): self
    {
        $I = $this->user;
        $I->dontSee(Translator::translate('SELECTED_SHIPPING_CARRIER'), $this->headings);
        $I->dontSeeElement($this->selectShippingButton);
        $I->see($this->noShippingMethodText, $this->paymentInformation);
        // If there is no shipping there is also no payment
        $I->see(Translator::translate('PAYMENT_INFORMATION'));
        $I->dontSeeElement($this->paymentOption);
        return $this;
    }

    public function goToNextStep(): OrderCheckout
    {
        $I = $this->user;
        $I->retryClick($this->nextStepButton);
        $orderCheckout = new OrderCheckout($I);
        $I->waitForElement($orderCheckout->breadCrumb);

        return $orderCheckout;
    }

    public function goToPreviousStep(): UserCheckout
    {
        $I = $this->user;
        $I->retryClick(Translator::translate('PREVIOUS_STEP'));
        $userCheckout = new UserCheckout($I);
        $I->waitForElement($userCheckout->breadCrumb);
        return $userCheckout;
    }
}
