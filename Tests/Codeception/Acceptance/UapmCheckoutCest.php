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
 * @group osc_paypal_checkout_uapm
 * @group osc_paypal_remote_login
 */
final class UapmCheckoutCest extends BaseCest
{
    public function checkoutWithUapmPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        //first decide to use sofort via paypal
        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_sofort')
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
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_sofort')
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

    public function checkoutWithSofortViaPayPalCancel(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with Sofort via PayPal cancels payment after redirect.');

        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_sofort')
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#successSubmit');
        $I->seeElement('#failureSubmit');
        $I->seeElement('#cancelSubmit');
        $I->click('#cancelSubmit');

        $I->switchToWindow();
        $I->seeElement("#PayPalButtonPaymentPage");
        //NOTE: simulation sends us error code on cancel
        $I->see(Translator::translate('MESSAGE_PAYMENT_AUTHORIZATION_FAILED'));

        //nothing changed
        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    public function checkoutWithSofortViaPayPalError(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with Sofort via PayPal runs into payment error after redirect.');

        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_sofort')
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#failureSubmit');
        $I->click('#failureSubmit');

        $I->switchToWindow();
        $I->seeElement("#PayPalButtonPaymentPage");
        $I->see(Translator::translate('MESSAGE_PAYMENT_AUTHORIZATION_FAILED'));

        //nothing changed
        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    public function checkoutWithSofortViaPayPalSuccess(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with Sofort via PayPal successfully places an order.');

        $I->seeNumRecords(0, 'osc_paypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oxidpaypal_sofort')
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#successSubmit');
        $I->click('#successSubmit');

        $I->switchToWindow();
        $I->seeNumRecords(1, 'osc_paypal_order');
        $I->seeNumRecords(2, 'oxorder');
        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId
            ]
        );

        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => Fixtures::get('details')['firstname']
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_APPROVED'));
        $I->seeElement('//input[@value="Capture"]');
        $I->see('119,60 EUR');

        //Order was not yet captured, so it should not be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
    }
}
