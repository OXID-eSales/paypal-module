<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Step\ProductNavigation;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use Codeception\Example;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
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
    public function _after(AcceptanceTester $I): void
    {
        $this->setProductAvailability($I, 1, 15);

        parent::_after($I);
    }

    protected function providerPaymentMethods(): array
    {
        return [
            ['paymentId' => PayPalDefinitions::SOFORT_PAYPAL_PAYMENT_ID],
            ['paymentId' => PayPalDefinitions::GIROPAY_PAYPAL_PAYMENT_ID]
        ];
    }

    /**
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I, Example $data): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));
        $paymentMethodId = $data['paymentId'];

        //first decide to use uapm via paypal
        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment($paymentMethodId)
            ->goToNextStep();
        $paymentCheckout = $orderCheckout->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        //change decision to standard PayPal
        //NOTE: this is approving PayPal 'brute force' by simulating PayPal redirect
        $token = $this->approvePayPalTransaction($I);

        //pretend we are back in shop after clicking PayPal button and approving the order
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));
        $I->see(Translator::translate('OSC_PAYPAL_PAY_UNLINK'));
        $I->click(Translator::translate('OSC_PAYPAL_PAY_UNLINK'));

        //change decision again to use uapm via PayPal
        $paymentCheckout = new PaymentCheckout($I);
        if ($I->seePageHasElement("//a[contains(@href, 'fnc=cancelPayPalPayment')]"))
        {
            $I->click(Translator::translate("OSC_PAYPAL_PAY_UNLINK"));
        }
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment($paymentMethodId)
            ->goToNextStep();
        $paymentCheckout = $orderCheckout->goToPreviousStep();
        $I->dontSee(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        //we now decide for PayPal again
        //NOTE: there's still a paypal order id in the session but with current implementation
        // it will be replaced by a fresh one
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");
        $newToken = $this->approvePayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);

        //we got a fresh paypal order in the session
        $I->assertNotEquals($token, $newToken);
    }

    /**
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalCancel(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest('logged in user with ' . $paymentMethodId . ' via PayPal cancels payment after redirect.');

        $I->seeNumRecords(0, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment($paymentMethodId)
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#successSubmit');
        $I->seeElement('#failureSubmit');
        $I->seeElement('#cancelSubmit');
        $I->click('#cancelSubmit');

        $I->switchToWindow();
        $I->seeElement('#payment_' . $paymentMethodId);
        //NOTE: simulation sends us error code on cancel
        $I->see(Translator::translate('MESSAGE_PAYMENT_AUTHORIZATION_FAILED'));

        //nothing changed
        $I->seeNumRecords(0, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);
    }

    /**
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalError(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal runs into payment error after redirect.'
        );

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment($paymentMethodId)
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#failureSubmit');
        $I->click('#failureSubmit');

        $I->switchToWindow();
        $I->seeElement('#payment_' . $paymentMethodId);
        $I->see(Translator::translate('MESSAGE_PAYMENT_AUTHORIZATION_FAILED'));

        //nothing changed
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    /**
     * NOTE: this test case requires a NOT working webhook. If webhook is working test will fail.
     *
     * @group oscpaypal_without_webhook
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalSuccessNoWebhook(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest('logged in user with ' . $paymentMethodId . ' via PayPal successfully places an order.');

        list($orderNumber, $orderId) = $this->doCheckout($I, $paymentMethodId);

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_APPROVED'));
        $I->seeElement('//input[@value="Capture"]');
        $I->see('119,60 EUR');

        //Order was not yet captured, so it should not be marked as paid (assuming we have no working webhook)
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
    }

    /**
     * NOTE: this test case requires a working webhook. On your local machine please use ngrok
     *       with correctly registered PayPal sandbox webhook.
     *       Test might be unstable depending on how fast PayPal sends notifications.
     *       And this test will be slow because webhook needs some wait time.
     *
     * @group oscpaypal_with_webhook
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalSuccessWebhook(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest('logged in user with ' . $paymentMethodId . ' via PayPal successfully places an order.');

        list($orderNumber, $orderId) = $this->doCheckout($I, $paymentMethodId);

        //give the webhook time to process all incoming events
        $I->wait(120);

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_COMPLETED'));
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        //PayPal should have sent the information about successful payment by now
        $this->assertOrderPaidAndFinished($I);
    }

    /**
     * NOTE: this test case requires a working webhook. On your local machine please use ngrok
     *       with correctly registered PayPal sandbox webhook.
     *       Test might be unstable depending on how fast PayPal sends notifications.
     *       And this test will be slow because webhook needs some wait time.
     *
     * @group oscpaypal_with_webhook
     * @dataProvider providerPaymentMethods
     */
    public function checkoutLastItemInStockWithUapmViaPayPal(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal successfully places an order for last available item.'
        );

        $this->setProductAvailability($I, 3, 1);

        list($orderNumber, $orderId) = $this->doCheckout($I, $paymentMethodId);

        //give the webhook time to process all incoming events
        $I->wait(90);

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_COMPLETED'));
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        //PayPal should have sent the information about successful payment by now
        $this->assertOrderPaidAndFinished($I);
    }

    /**
     * @group oscpaypal_uapm_dropoff
     * @group oscpaypal_uapm_dropoff_cancel
     *
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalCancelDropOff(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal cancels payment after redirect and drops off, then reopens shop and tries again to order'
        );

        $this->completeUapmPayment($I, $paymentMethodId, 'cancel', true);

        $I->waitForPageLoad();
        $I->seeElement('#redirectSubmit');

        //NOTE: sandbox did not send any event in this case on last manual check

        //at this point we seen an unfinished order in the database
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        //assume user is still logged in with same session and tries once more to finalize the order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //empty order is gone from database on order controller render
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //As we have a dropoff scenario and no information was sent via webhook (tried a lot, there's none)
        //this is an incomplete order which should be cancelled automatically after a certain time.
        //That kind of order must not be paid, finished, have a transaction id and related paypal order
        //must still show 'PAYER_ACTION_REQUIRED'. Then it is ok to remove.

        //still empty order in database at this time
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        //let's wait and then try to submit order once more
        $I->wait(60);

        //cannot complete the order, shop runs into Order::ORDER_STATE_ORDEREXISTS and redirects to start page
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->see(Translator::translate('START_BARGAIN_HEADER'));

        //still empty order in database at this time TODO: improve
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);
    }

    /**
     * @group oscpaypal_uapm_dropoff
     * @group oscpaypal_uapm_dropoff_fail
     *
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalPaymentFailDropOff(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal has failed payment after redirect and drops off, then reopens shop and tries again to order'
        );

        $this->completeUapmPayment($I, $paymentMethodId, 'failure', true);

        $I->waitForPageLoad();
        $I->seeElement('#redirectSubmit');

        //NOTE: sandbox did not send any event in this case on last manual check

        //at this point we seen an unfinished order in the database
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        //assume user is still logged in with same session and tries once more to finalize the order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //empty order is gone from database on order controller render
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //As we have a dropoff scenario and no information was sent via webhook (tried a lot, there's none)
        //this is an incomplete order which should be cancelled automatically after a certain time.
        //That kind of order must not be paid, finished, have a transaction id and related paypal order
        //must still show 'PAYER_ACTION_REQUIRED'. Then it is ok to remove.

        //still empty order in database at this time
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);

        //let's wait and then try to submit order once more
        $I->wait(60);

        //cannot complete the order, shop runs into Order::ORDER_STATE_ORDEREXISTS and redirects to start page
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->see(Translator::translate('START_BARGAIN_HEADER'));

        //still empty order in database at this time TODO: improve
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'PAYER_ACTION_REQUIRED']);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);
    }

    /**
     * @group oscpaypal_with_webhook
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalPaymentSuccessDropOff(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal has successful payment after redirect, drops off and retries order after webhook finished it.'
        );

        $this->completeUapmPayment($I, $paymentMethodId, 'success', true);

        $I->waitForPageLoad();
        $I->seeElement('#redirectSubmit');

        //NOTE: we need the webhook events to get information about successful payment
        $I->wait(120);

        //at this point we see a completely finished order in the database
        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'COMPLETED']);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid', ['oscpaypalstatus' => 'COMPLETED']);
        $orderNumberFromDb = $I->grabFromDatabase('oxorder', 'oxordernr', ['OXID' => $orderId]);
        $I->assertGreaterThan(0, $orderNumberFromDb);
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);
        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('OK', $transStatus);

        //assume user is still logged in with same session and tries once more to finalize the (already completed) order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //NOTE: order is already completed
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $I->waitForPageLoad();
        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertSame($orderNumberFromDb, $orderNumber);

        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => $orderNumber, 'oxtransstatus' => 'OK']);
        $orderIdCurrent = $I->grabFromDatabase('oxorder', 'oxid', ['oxordernr' => $orderNumber]);
        $I->assertSame($orderId, $orderIdCurrent);

        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(
            1,
            'oscpaypal_order',
            [
                'oscpaypalstatus' => 'COMPLETED',
                'oxorderid' => $orderId
            ]
        );

        $this->assertOrderPaidAndFinished($I);
    }

    /**
     * @group oscpaypal_with_webhook
     *
     * @dataProvider providerPaymentMethods
     */
    public function checkoutWithUapmViaPayPalPaymentSuccessDropOffQuickRetry(AcceptanceTester $I, Example $data): void
    {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId .
            ' via PayPal has successful payment after redirect, drops off and retries order before webhook finished it.'
        );

        $this->completeUapmPayment($I, $paymentMethodId, 'success', true);

        $I->waitForPageLoad();
        $I->seeElement('#redirectSubmit');

        //NOTE: do not wait for webhook, reload and try again

        //at this point we see a completely finished order in the database
        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => 0]);
        $I->seeNumRecords(0, 'oscpaypal_order', ['oscpaypalstatus' => 'COMPLETED']);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('NOT_FINISHED', $transStatus);

        //assume user is still logged in with same session and tries once more to finalize the (already completed) order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //NOTE: order is already being executed but we might still wait for webhook events
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->waitForPageLoad();
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));

        //now wait for webhook
        $I->wait(120);
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->waitForPageLoad();
        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(0, $orderNumber);

        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);
        $I->seeNumRecords(1, 'oxorder', ['oxordernr' => $orderNumber, 'oxtransstatus' => 'OK']);
        $orderIdCurrent = $I->grabFromDatabase('oxorder', 'oxid', ['oxordernr' => $orderNumber]);
        $I->assertSame($orderId, $orderIdCurrent);

        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(
            1,
            'oscpaypal_order',
            [
                'oscpaypalstatus' => 'COMPLETED',
                'oxorderid' => $orderId
            ]
        );

        $this->assertOrderPaidAndFinished($I);
    }

    /**
     * @dataProvider providerPaymentMethods
     *
     * @group oscpaypal_with_webhook
     */
    public function checkoutWithAcdcViaPayPalImpatientCustomerOtherPaymentMethod(
        AcceptanceTester $I,
        Example $data
    ): void {
        $paymentMethodId = $data['paymentId'];

        $I->wantToTest(
            'logged in user with ' . $paymentMethodId . ' has successful payment after redirect, drops off ' .
            ' and retries order with different payment before webhook has finished'
        );

        $this->completeUapmPayment($I, $paymentMethodId, 'success', true);

        $I->waitForPageLoad();
        $I->seeElement('#redirectSubmit');

        //customer is very impatient, reloads order page and tries again
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        //but shop detects that order is already too far progressed to select different payment method
        //we are redirected to order
        $I->see(Translator::translate('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'));
        $I->dontSee(Translator::translate('SELECT_SHIPPING_METHOD'));

        //Give webhook time to finish. NOTE: sometimes events get delayed, you can see this in PayPal developer account.
        //So if test fails with order not paid webhook event might not have been sent in time. In this case rerun test.
        $I->wait(120);

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();
        $I->waitForPageLoad();

        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));
        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $this->assertOrderPaidAndFinished($I);
    }

    private function doCheckout(AcceptanceTester $I, string $paymentMethodId): array
    {
        $this->completeUapmPayment($I, $paymentMethodId);

        $I->switchToWindow();
        $I->seeNumRecords(2, 'oscpaypal_order');
        $I->seeNumRecords(2, 'oxorder');
        $I->see(Translator::translate('THANK_YOU_FOR_ORDER'));

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
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
        $I->seeElement('//input[@id="refundAmount"]');
        $I->see('119,60 EUR');
        return [$orderNumber, $orderId];
    }

    private function completeUapmPayment(
        AcceptanceTester $I,
        string $paymentMethodId,
        string $submit = 'success',
        bool $dropOff = false
    ): void {
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment($paymentMethodId)
            ->goToNextStep();
        $orderCheckout->submitOrder();

        //Order was not yet captured, so it should not be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);
        //simulated payment popup
        $I->switchToLastWindow();
        $I->seeElement('#successSubmit');
        $I->seeElement('#failureSubmit');
        $I->seeElement('#cancelSubmit');
        if ($dropOff) {
            $I->executeJS('document.getElementById("dropOffPage").checked=true');
        }
        $I->click('#' . $submit . 'Submit');
    }
}
