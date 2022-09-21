<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use Codeception\Example;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalLogin;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\ThankYou;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_standard
 * @group osc_paypal_remote_login
 */
final class PayPalStandardCheckoutCest extends BaseCest
{
    public function _after(AcceptanceTester $I): void
    {
        $I->updateModuleConfiguration('oscPayPalStandardCaptureStrategy', 'directly');

        parent::_after($I);
    }

    public function checkoutWithPaypalStandardAndRefund(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same. Refund after order completion.'
        );

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);

        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        //check database
        $this->assertOrderPaidAndFinished($I);

        //click refund
        $I->click('//input[@value="Refund"]');
        $I->see('Refunded');
        $I->dontSeeElement('//input[@value="Refund"]');

        //check database
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'REFUNDED']);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $transactionId = $I->grabFromDatabase(
            'oscpaypal_order',
            'oscpaypaltransactionid',
            ['oscpaypalstatus' => 'REFUNDED']
        );
        $I->assertNotEmpty($transactionId);
    }

    public function checkoutWithPaypalStandardCaptureLater(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same. '
        );

        $I->updateModuleConfiguration('oscPayPalStandardCaptureStrategy', 'manually');

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);

        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Approved');
        $I->seeElement('//input[@value="Capture"]');
        $I->see('119,60 EUR');

        //check database
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $I->assertEmpty($I->grabFromDatabase('oscpaypal_order', 'oscpaypaltransactionid'));

        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('0000-00-00'), $oxPaid);

        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('NOT_FINISHED', $transStatus);

        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'APPROVED']);

        //let's see what capture does
        $I->click('//input[@value="Capture"]');
        $I->waitForPageLoad();

        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        //check database
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $transactionId = $I->grabFromDatabase('oscpaypal_order', 'oscpaypaltransactionid');
        $I->assertNotEmpty($transactionId);

        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'COMPLETED']);

        $I->assertEquals(
            $transactionId,
            $I->grabFromDatabase(
                'oscpaypal_order',
                'oscpaypaltransactionid',
                [
                    'oscpaypalstatus' => 'COMPLETED'
                ]
            )
        );

        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);

        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('OK', $transStatus);
    }

    public function checkoutWithPaypalStandardDifferentEmail(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are different.'
        );

        $this->proceedToPaymentStep($I);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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

        //check database
        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithPaypalStandardDeliveryAddressChange(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same. Delivery address change in last order step.'
        );

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep();
        $this->submitOrderWithUpdatedDeliveryAddress($I);

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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
                'OXBILLFNAME' => $_ENV['sBuyerFirstName'],
                'OXDELFNAME' => self::DELIVERY_FIRSTNAME,
                'OXDELCOMPANY' => self::DELIVERY_COMPANY,
                'OXDELADDINFO' => self::DELIVERY_OXADDINFO
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithPaypalStandardDeliveryAddressChangeDifferentMail(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are different. Delivery address change in last order step.'
        );

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep();
        $this->submitOrderWithUpdatedDeliveryAddress($I);

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => self::DELIVERY_FIRSTNAME,
                'OXDELCOMPANY' => self::DELIVERY_COMPANY,
                'OXDELADDINFO' => self::DELIVERY_OXADDINFO
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithPaypalStandardDropOffAndReloadShopOrderPage(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same. User drops off on PayPal page and reloads order page.'
        );

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(0, 'oxorder', ['oxpaymenttype' => 'oscpaypal']);

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);

        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        //we have an unfinished order with related paypal order only existing in session
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder', ['oxpaymenttype' => 'oscpaypal', 'oxordernr' => '0']);

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->loginToPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        //not yet confirmed with PayPal
        $I->amOnUrl($this->getShopUrl() . '?cl=order');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
        $I->seeNumRecords(0, 'oxorder', ['oxpaymenttype' => 'oscpaypal', 'oxordernr' => '0']);

        //now complete the order
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        //check database
        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithPaypalStandardDropOffAndReloadShopPaymentPage(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same. User drops off on PayPal page and reloads payment page.'
        );

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(0, 'oxorder', ['oxpaymenttype' => 'oscpaypal']);

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        //we have an unfinished order with related paypal order only existing in session
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder', ['oxpaymenttype' => 'oscpaypal', 'oxordernr' => '0']);
        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['oxordernr' => '0']);

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->loginToPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        //not yet confirmed with PayPal
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment('oxidcashondel')
            ->goToNextStep()
            ->submitOrder();

        /** @var ThankYou $thankYouPage */
        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        //check database
        $finalizedOrderId = $I->grabFromDatabase('oxorder', 'oxid', ['oxordernr' => $orderNumber]);
        $I->assertNotEquals($orderId, $finalizedOrderId);

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['oxordernr' => $orderNumber]);
        $I->assertStringStartsWith(date('0000-00-00'), $oxPaid);  //cash on del is not yet paid

        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['oxordernr' => $orderNumber]);
        $I->assertStringStartsWith('OK', $transStatus);

        $I->assertEmpty($I->grabFromDatabase('oxorder', 'oxtransid', ['oxordernr' => $orderNumber]));
        $I->assertSame(
            'oxidcashondel',
            $I->grabFromDatabase('oxorder', 'OXPAYMENTTYPE', ['oxordernr' => $orderNumber])
        );

        //order was not pay with PayPal
        $I->seeNumRecords(0, 'oscpaypal_order');
    }

    /**
     * @dataProvider providerStock
     *
     * @group oscpaypal_stock
     */
    public function checkoutWithPaypalStandardLastItemsOnStock(AcceptanceTester $I, Example $data): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are different. Checking out last items on stock.'
        );

        $this->setProductAvailability($I, $data['stockflag'], Fixtures::get('product')['amount']);

        $this->proceedToPaymentStep($I);

        /** @var PaymentCheckout $paymentCheckout */
        $paymentCheckout = new PaymentCheckout($I);
        $paymentCheckout->selectPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ->goToNextStep()
            ->submitOrder();

        /** @var PayPalLogin $payPalLogin */
        $payPalLogin = new PayPalLogin($I);
        $payPalLogin->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        /** @var ThankYou $thankYouPage */
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

        //check database
        $this->assertOrderPaidAndFinished($I);
    }
}
