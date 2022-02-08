<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_standard
 * @group osc_paypal_remote_login
 */
final class CheckoutCest extends BaseCest
{
    public function checkoutWithPaypalStandard(AcceptanceTester $I): void
    {
        $I->wantToTest('checking out as logged in user with PayPal as payment method. Shop login and PayPal login mail are the same.');

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToPaymentStep($I, $_ENV['sBuyerLogin']);
        $token = $this->approvePayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $orderNumber = $this->finalizeOrder($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
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

        //Oder was captured, so it should be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);
    }

    public function checkoutWithPaypalStandardDifferentEmail(AcceptanceTester $I): void
    {
        $I->wantToTest('checking out as logged in user with PayPal as payment method. Shop login and PayPal login mail are different.');

        $this->proceedToPaymentStep($I);
        $token = $this->approvePayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $orderNumber = $this->finalizeOrder($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('119,60 EUR');

        //Oder was captured, so it should be marked as paid
        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);
    }

    public function changeBasketDuringCheckout(AcceptanceTester $I)
    {
        $I->wantToTest('changing basket contents after payment was authorized');

        $this->proceedToPaymentStep($I);
        $token = $this->approvePayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        $I->amOnUrl($this->getShopUrl() . '/en/cart');

        $product = Fixtures::get('product');
        $basket = new Basket($I);
        $basket->addProductToBasketAndOpenBasket($product['oxid'], $product['amount'], 'basket');

        //finalize order in previous tab
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $orderNumber = $this->finalizeOrder($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '239.2'
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('239,20 EUR');
        $I->dontSee('119,60 EUR');
    }

    public function checkoutWithPaypalInBasketStep(AcceptanceTester $I): void
    {
        $I->wantToTest('checking out as logged in user with PayPal in basket step. Shop login and PayPal login mail are the same.');

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin']);
        $token = $this->approvePayPalTransaction($I);

        //We just skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));

        $orderNumber = $this->finalizeOrderInOrderStep($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '119.6'
            ]
        );
    }

    public function checkoutWithPaypalInBasketStepDifferentMail(AcceptanceTester $I): void
    {
        $I->wantToTest('checking out as logged in user with PayPal in basket step. Shop login and PayPal login mail are different.');

        $this->proceedToBasketStep($I);
        $token = $this->approvePayPalTransaction($I);

        //We just skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));

        $orderNumber = $this->finalizeOrderInOrderStep($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '119.6'
            ]
        );
    }
}
