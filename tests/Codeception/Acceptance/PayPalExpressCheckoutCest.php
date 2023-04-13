<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use Codeception\Example;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalLogin;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Page\Checkout\ThankYou;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_express
 * @group osc_paypal_remote_login
 */
final class PayPalExpressCheckoutCest extends BaseCest
{
    public function changeBasketDuringExpressCheckout(AcceptanceTester $I)
    {
        $I->wantToTest('logged in user changes basket contents after express payment was authorized');

        $home = $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        //add product to basket and simulate express checkout
        $this->fillBasket($I);

        $token = $this->approveExpressPayPalTransaction($I);
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
            'oscpaypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => '239.2',
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => Fixtures::get('details')['firstname']
            ]
        );

        //As we have a PayPal order now, also check admin
        $this->openOrderPayPal($I, (string) $orderNumber);
        $I->see(Translator::translate('OSC_PAYPAL_HISTORY_PAYPAL_STATUS'));
        $I->see('Completed');
        $I->seeElement('//input[@value="Refund"]');
        $I->see('239,20 EUR');
        $I->dontSee('119,60 EUR');

        //check database
        $this->assertOrderPaidAndFinished($I);
    }

    public function checkoutWithPaypalExpressInBasketStep(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal in basket step. ' .
            'Shop login and PayPal login mail are the same.'
        );

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin']);
        $token = $this->approveExpressPayPalTransaction($I);

        //We just skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));

        $orderNumber = $this->finalizeOrderInOrderStep($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
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
                'OXBILLFNAME' => $_ENV['sBuyerFirstName'],
                'OXDELFNAME' => ''
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

    public function checkoutWithPaypalInBasketStepDifferentMail(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checking out as logged in user with PayPal in basket step.'
                . ' Shop login and PayPal login mail are different.'
        );

        $this->proceedToBasketStep($I);
        $token = $this->approveExpressPayPalTransaction($I);

        //We just skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));

        $orderNumber = $this->finalizeOrderInOrderStep($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
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
                'OXDELFNAME' => ''
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

    /**
     * @dataProvider providerStock
     *
     * @group oscpaypal_stock
     */
    public function checkoutLastItemInStockWithPUIViaPayPal(AcceptanceTester $I, Example $data): void
    {
        $I->wantToTest(
            'logged in user with Express successfully places an order for last available items.'
        );

        $this->setProductAvailability($I, $data['stockflag'], Fixtures::get('product')['amount']);

        $this->proceedToBasketStep($I);
        $token = $this->approveExpressPayPalTransaction($I);

        //We just skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));

        $orderNumber = $this->finalizeOrderInOrderStep($I);
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
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
                'OXDELFNAME' => ''
            ]
        );

        //check database
        $this->assertOrderPaidAndFinished($I);
    }
}
