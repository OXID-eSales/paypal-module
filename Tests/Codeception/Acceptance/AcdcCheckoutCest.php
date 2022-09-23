<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

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
 * @group osc_paypal_checkout_acdc
 * @group osc_paypal_remote_login
 */
final class AcdcCheckoutCest extends BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    public function checkoutWithAcdcPayPalDoesNotInterfereWithStandardPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest('switching between payment methods');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        //first decide to use credit card via paypal
        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
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

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
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

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
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
     * Test must work with and without webhook
     *
     * @group oscpaypal_without_webhook
     * @group oscpaypal_with_webhook
     */
    public function checkoutWithAcdcViaPayPalImpatientCustomer(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with ACDC enters CC credentials and clicks order now more than once');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
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

        //Give webhook time to finish. NOTE: sometimes events get delayed, you can see this in PayPal developer account.
        //So if test fails with order not paid webhook event might not have been sent in time. In this case rerun test.
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

    /**
     * Test must work with and without webhook
     *
     * @group oscpaypal_without_webhook
     * @group oscpaypal_with_webhook
     */
    public function checkoutWithAcdcViaPayPalImpatientCustomerOtherPaymentMethod(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'logged in user with ACDC enters CC credentials and start execute order, ' .
            'returns to payment step and wants to execute order again with different payment method'
        );

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
            ->goToNextStep();
        $I->waitForPageLoad();

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $this->fillInCardFields($I);
        $orderCheckout->submitOrder();
        $I->wait(10);

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

    /**
     * NOTE: this test case requires a working webhook. On your local machine please use ngrok
     *       with correctly registered PayPal sandbox webhook.
     *       Test might be unstable depending on how fast PayPal sends notifications.
     *       And this test will be slow because webhook needs some wait time.
     *
     * @dataProvider providerStock
     *
     * @group oscpaypal_stock
     * @group oscpaypal_with_webhook
     */
    public function checkoutLastItemInStockWithACDCViaPayPal(AcceptanceTester $I, Example $data): void
    {
        $I->wantToTest(
            'logged in user with acdc via PayPal successfully places an order for last available item.'
        );

        $this->setProductAvailability($I, $data['stockflag'], Fixtures::get('product')['amount']);

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
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
    }

    /**
     * NOTE: this test case requires a working webhook. On your local machine please use ngrok
     *       with correctly registered PayPal sandbox webhook.
     *       Test might be unstable depending on how fast PayPal sends notifications.
     *       And this test will be slow because webhook needs some wait time.
     *
     * @group oscpaypal_stock
     * @group oscpaypal_with_webhook
     */
    public function checkoutLastItemInStockOutOfStockWithACDCViaPayPal(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'logged in user with acdc via PayPal cannot place an order for not buyable out of stock item.'
        );

        $this->setProductAvailability($I, 2, Fixtures::get('product')['amount']);

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $paymentCheckout->selectPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID)
            ->goToNextStep();
        $I->waitForPageLoad();

        //someone else was faster
        $this->setProductAvailability($I, 2, 0);

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $this->fillInCardFields($I);
        $orderCheckout->submitOrder();

        //Give page time to finish
        $I->wait(30);

        $I->see(sprintf(
            Translator::translate(
                'ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST'
            ),
            Fixtures::get('product')['oxartnum']
        ));

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
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
