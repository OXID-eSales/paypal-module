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
 * @group osc_paypal_checkout_pui
 */
final class PuiCheckoutCest extends BaseCest
{
    public function checkoutWithPuiViaPayPalMissingRequiredFields(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with PUI via PayPal cannot place order without mandatory fields');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oscpaypal_pui')
            ->goToNextStep();
        $orderCheckout->submitOrder();

        $I->waitForPageLoad();
        $I->seeElement("#orderConfirmAgbBottom");

        //nothing changed
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    public function checkoutWithPuiViaPayPalError(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with PUI via PayPal runs into payment error (unparsable phone).');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $I->updateInDatabase(
            'oxuser',
            [
                'oxfon' => 'lalalala',
                'oxbirthdate' => '2000-04-01'
            ],
            [
                'oxusername' => Fixtures::get('userName')
            ]
        );

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oscpaypal_pui')
            ->goToNextStep();
        $orderCheckout->submitOrder();

        $I->waitForPageLoad();
        $I->seeElement("#PayPalButtonPaymentPage");
        $I->see(Translator::translate('MESSAGE_UNAVAILABLE_SHIPPING_METHOD'));

        //nothing changed
        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');
    }

    public function checkoutWithPUIViaPayPalSuccessEnterMandatoryFields(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with PUI via PayPal successfully places an order.');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oscpaypal_pui')
            ->goToNextStep();

        $I->executeJS('document.getElementsByName("pui_required[phonenumber]")[0].value = "040111222333";');
        $I->executeJS('document.getElementsByName("pui_required[birthdate][year]")[0].value = "2000";');
        $I->executeJS('document.getElementsByName("pui_required[birthdate][month]")[0].value = "4";');
        $I->executeJS('document.getElementsByName("pui_required[birthdate][day]")[0].value = "1";');

        $orderCheckout->submitOrder();
        $I->waitForPageLoad();

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
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_APPROVED'));
        $I->seeElement('//input[@value="Capture"]');
        $I->see('119,60 EUR');

        //Order was not yet captured, so it should not be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
    }

    public function checkoutWithPUIViaPayPalSuccess(AcceptanceTester $I): void
    {
        $I->wantToTest('logged in user with PUI via PayPal successfully places an order.');

        $I->seeNumRecords(0, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oxorder');

        $I->updateInDatabase(
            'oxuser',
            [
                'oxfon' => '040111222333',
                'oxbirthdate' => '2000-04-01'
            ],
            [
                'oxusername' => Fixtures::get('userName')
            ]
        );

        $this->proceedToPaymentStep($I, Fixtures::get('userName'));

        $paymentCheckout = new PaymentCheckout($I);
        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = $paymentCheckout->selectPayment('oscpaypal_pui')
            ->goToNextStep();
        $orderCheckout->submitOrder();
        $I->waitForPageLoad();

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
        $I->see(Translator::translate('OSC_PAYPAL_STATUS_APPROVED'));
        $I->seeElement('//input[@value="Capture"]');
        $I->see('119,60 EUR');

        //Order was not yet captured, so it should not be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith('0000-00-00', $oxPaid);
    }
}
