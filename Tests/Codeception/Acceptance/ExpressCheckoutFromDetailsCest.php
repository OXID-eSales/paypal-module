<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Checkout\Basket as BasketPage;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\ProductNavigation;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_express
 * @group osc_paypal_express_details
 */
final class ExpressCheckoutFromDetailsCest extends BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->clearShopCache();
        $I->setPayPalBannersVisibility(false);
        $I->updateConfigInDatabase('blUseStock', false, 'bool');
        $I->updateConfigInDatabase('bl_perfLoadPrice', true, 'bool');
        $I->updateConfigInDatabase('iNewBasketItemMessage', false, 'bool');
        $I->updateModuleConfiguration('blPayPalLoginWithPayPalEMail', false);
        $this->ensureShopUserData($I);
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->ensureShopUserData($I);
        $this->enableExpressButtons($I);

        parent::_after($I);
    }

    public function testExpressCheckoutFromDetailsButton(AcceptanceTester $I): void
    {
        $I->wantToTest('checkout from details page with empty cart. Customer is logged in.');

        $this->enableExpressButtons($I, false);
        $I->updateModuleConfiguration('blPayPalShowProductDetailsButton', true);

        $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        //We have an empty cart at this time
        //NOTE: manually express checkout works if we have no sid cookie at this point,
        //      but codeception test did not have sid cookie et end of approveOrder call.
        //So for now, we test with a logged in customer
        $token = $this->approvePayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        //button will not be shown anymore because of started paypal session
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->dontSeeElement("#PayPalButtonProductMain");

        $this->fromBasketToPayment($I);
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
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname']
            ]
        );
    }

    public function testDetailsButtonPlacementWithPrefilledCart(AcceptanceTester $I): void
    {
        $I->wantToTest('checkout from details page from clean session and filled cart. Customer is guest buyer without shop account.');

        $this->enableExpressButtons($I);
        $I->updateModuleConfiguration('blPayPalShowProductDetailsButton', true);

        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        $token = $this->approvePayPalTransaction($I, '&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        //button will not be shown on started paypal session
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->dontSeeElement("#PayPalButtonProductMain");

        $this->fromBasketToPayment($I);
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
                'OXTOTALORDERSUM' => 5 * Fixtures::get('product')['bruttoprice_single'], //the original 4 plus one from details
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }

    public function testRepeatedCreateOrderCallNotAllowedWithSession(AcceptanceTester $I): void
    {
        $I->wantToTest('calling proxy createOrder only creates one PayPal order per session');

        $this->enableExpressButtons($I);
        $I->dontSeeCookie('sid');
        $I->dontSeeCookie('sid_key');

        //send this as post request
        $I->postTo(
            $this->getShopUrl() . '/index.php?cl=oscpaypalproxy&fnc=createOrder&context=continue&aid=' . Fixtures::get('product')['oxid']
        );

        $response = $I->grabJsonResponseAsArray();
        $paypalOrderId = $response['id'];
        $I->assertNotEmpty($paypalOrderId);
        $sid = $I->extractSidFromResponseCookies();
        $I->assertNotEmpty($sid);

        $I->postTo(
            $this->getShopUrl() . '/index.php?cl=oscpaypalproxy&fnc=createOrder&context=continue&aid=' . Fixtures::get('product')['oxid'],
            ['Cookie' => 'language=0; sid=' . $sid . ';sid_key=oxid']
        );
        $response = $I->grabJsonResponseAsArray();
        $I->assertSame(['ERROR' => 'PayPal session already started.'], $response);

        //retry but do not send sid cookie, a new session will be started with new PayPal order
        $I->clearShopCache();
        $I->postTo(
            $this->getShopUrl() . '/index.php?cl=oscpaypalproxy&fnc=createOrder&context=continue&aid=' . Fixtures::get('product')['oxid']
        );
        $response = $I->grabJsonResponseAsArray();
        $I->assertNotEquals($paypalOrderId, $response['id']);
        $I->assertNotEquals($sid,$I->extractSidFromResponseCookies());
    }

    public function testExpressCheckouFromDetailsAutomaticLogin(AcceptanceTester $I): void
    {
        $I->wantToTest('checkout from details page with empty cart. Customer is not logged in but has shop account with password.');

        $this->enableExpressButtons($I);
        $this->setUserNameSameAsPayPal($I);
        $I->updateModuleConfiguration('blPayPalLoginWithPayPalEMail', true);

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");
        $I->dontSeeCookie('sid');
        $I->dontSeeCookie('sid_key');

        $token = $this->approveAnonymousPayPalTransaction($I, '&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        //user was logged in and can open orders page
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->waitForPageLoad();

        $I->see(Translator::translate('MESSAGE_SUBMIT_BOTTOM'));
        $I->see(Translator::translate('MY_ACCOUNT'));
        $I->dontSee(Translator::translate('LOGIN'));

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
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }
}


