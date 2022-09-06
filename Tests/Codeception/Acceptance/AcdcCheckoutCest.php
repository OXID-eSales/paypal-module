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
final class AcdcCheckoutCest extends BaseCest
{
    public function checkoutWithAcdcPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        //first decide to use credit card via paypal
        $paymentCheckout = new PaymentCheckout($I);
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

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $I->wait(20);

        $I->see(Translator::translate('OSC_PAYPAL_ACDC_PLEASE_RETRY'));

        $I->seeNumRecords(0, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);
    }

    /**
     * @group oscpaypal_with_webhook
     */
    public function checkoutWithAcdcViaPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with ACDC enters CC credentials and clicks order now');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment('oscpaypal_acdc')
            ->goToNextStep();
        $I->waitForPageLoad();

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $this->fillInCardFields($I);
        $orderCheckout->submitOrder();

        //Give page time to finish
        $I->wait(30);

        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));
        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        //Give webhook time to finish. NOTE: sometimes events get delayed, you can see this in PayPal developer account.
        //So if test fails with order not paid webhook event might not have been sent in time. In this case rerun test.
        $I->wait(60);

        $this->assertOrderPaidAndFinished($I);

        /*
        //NOTE: this test will only pass if we have a valid webhook to handle events
        //      In case there is no working webhook, we will have the following order state: unfinished, not paid
        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('NOT_FINISHED', $transStatus);
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'SAVED']);
        $I->seeNumRecords(0, 'oscpaypal_order', ['oscpaypalstatus' => 'COMPLETED']);
        */
    }

    /**
     * @group mine
     * @group oscpaypal_with_webhook
     */
    public function checkoutWithAcdcViaPayPalImpatientCustomer(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with ACDC enters CC credentials and clicks order now more than once');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment('oscpaypal_acdc')
            ->goToNextStep();
        $I->waitForPageLoad();

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $this->fillInCardFields($I);
        $orderCheckout->submitOrder();
        $I->wait(10);

        //customer is very impatient, reloads order page and tries again
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //order execution is in progress so we should not see any card fields
        $I->dontSeeElement("#card_form");

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->waitForPageLoad();
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //now it waits for webhook events
        $I->wait(120);

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));
        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithAcdcViaPayPalImpatientCustomerOtherPaymentMethod(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'logged in user with ACDC enters CC credentials and start execute order, ' .
            'returns to payment step and executes order again with different payment method'
        );

        //TODO
        $I->markTestIncomplete('TODO implement test');
    }

    private function fillInCardFields(AcceptanceTester $I): void
    {
        $I->waitForElementVisible("#card_form");
        $I->seeElement("#cvv");
        $I->click("#cvv");
        $I->type($_ENV['acdcCreditCardCVV']);

        $I->seeElement("#card-holder-name");
        $I->click("#card-holder-name");
        $I->type(Fixtures::get('details')['firstname'] . ' ' . Fixtures::get('details')['lastname']);

        $I->seeElement("#expiration-date");
        $I->click("#expiration-date");
        $I->type($_ENV['acdcCreditCardExpirationDate']);

        $I->seeElement("#card-number");
        $I->click("#card-number");
        $I->type($_ENV['acdcCreditCardNumber']);
    }
}
