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
    public function checkoutWithPaypalStandard(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal as payment method.'
            . ' Shop login and PayPal login mail are the same.'
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

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

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
}
