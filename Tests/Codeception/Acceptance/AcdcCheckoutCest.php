<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_acdc
 * @group osc_paypal_remote_login
 */
final class AcdcCheckoutCest extends BaseCest
{
    public function checkoutWithAcdcPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        //first decide to use credit card via paypal
        $paymentCheckout = new PaymentCheckout($I);
        if ($I->seePageHasElement("//a[contains(@href, 'fnc=cancelPayPalPayment')]"))
        {
            $I->click(Translator::translate('OSC_PAYPAL_PAY_UNLINK'));
        }
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oscpaypal_acdc')
            ->goToNextStep();
        $paymentCheckout = $orderCheckout->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $I->amOnPage('/en/cart');
        $token = $this->approvePayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));
        $I->dontSeeElement('#payment_oscpaypal_acdc');
        $I->click(Translator::translate('OSC_PAYPAL_PAY_UNLINK'));

        $paymentCheckout->selectPayment('oscpaypal_acdc')
            ->goToNextStep()
            ->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));
    }

    public function checkoutWithAcdcViaPayPalNoCreditCardFieldsFilled(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with ACDC clicks order now without entering CC credentials');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment('oscpaypal_acdc')
            ->goToNextStep();
        $I->waitForPageLoad();
        $I->waitForElementVisible("#card_form");

        //This is as far as we get, looks like codeception cannot interfere with paypal JS
        $I->seeElement("#card_form");
    }
}
