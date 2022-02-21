<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Step\ProductNavigation;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_acdc
 * @group osc_paypal_remote_login
 */
final class AcdcCheckoutCestCheckoutCest extends BaseCest
{
    public function checkoutWithAcdcPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        //first decide to use sofort via paypal
        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_acdc')
            ->goToNextStep();
        $paymentCheckout = $orderCheckout->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        //change decision to standard PayPal
        $token = $this->approvePayPalTransaction($I);

        //pretend we are back in shop after clicking PayPal button and approving the order
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        //change decision again to use Sofort via PayPal
        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_acdc')
            ->goToNextStep();
        $paymentCheckout = $orderCheckout->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        //we now decide for PayPal again
        //NOTE: there's still a paypal order id in the session but with current implementation it will be replaced by a fresh one
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");
        $newToken = $this->approvePayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);

        //we got a fresh paypal order in the session
        $I->assertNotEquals($token, $newToken);
    }

    public function checkoutWithAcdcViaPayPalNoCreditCardFieldsFilled(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with ACDC clicks order now without entering CC credentials');

        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment('oxidpaypal_acdc')
            ->goToNextStep();
        $I->waitForPageLoad();
        $I->see('Creditcard (via PayPal)');
        $I->waitForElementVisible("#card_form");

        //This is as far as we get, look slike codecetion cannot interfere with paypal JS
        $I->seeElement("#card_form");
    }
}
