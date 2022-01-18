<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Home;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Page\Checkout\Basket as BasketCheckout;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalLogin;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_checkout_standard
 */
final class CheckoutCest extends BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->clearShopCache();
        $I->setPayPalBannersVisibility(false);
        $I->updateConfigInDatabase('blUseStock', false, 'bool');
        $I->updateConfigInDatabase('bl_perfLoadPrice', true, 'bool');
        $I->updateConfigInDatabase('iNewBasketItemMessage', false, 'bool');
    }

    public function checkoutWithPaypalStandard(AcceptanceTester $I): void
    {
        $I->wantToTest('checking out as logged in user with PayPal as payment method');

        $I->updateModuleConfiguration('blPayPalShowCheckoutButton', true);

        $home = $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        //add product to basket and start checkout
        $product = Fixtures::get('product');
        $basket = new Basket($I);
        $basket->addProductToBasketAndOpenBasket($product['oxid'], $product['amount'], 'basket');
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));

        $I->amOnPage('/en/cart');
        $basketPage = new BasketCheckout($I);
        $basketPage->goToNextStep()
            ->goToNextStep();

        $I->see(Translator::translate('PAYMENT_METHOD'));
        $I->seeElement("#PayPalButtonPaymentPage");

        //workaround to approve the transaction on PayPal side
        $loginPage = new PayPalLogin($I);
        $loginPage->openPayPalApprovalPage($I);
        $token = $loginPage->getToken();
        $loginPage->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=payment');
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $paymentPage = new PaymentCheckout($I);
        $paymentPage->goToNextStep()
            ->submitOrder();

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();

        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'osc_paypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $token
            ]
        );
    }
}
