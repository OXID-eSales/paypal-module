<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Page\Checkout\UserCheckout;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\ProductNavigation;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout
 * @group osc_paypal_express
 * @group osc_paypal_express_details
 * @group osc_paypal_remote_login
 */
final class ExpressCheckoutFromDetailsCest extends BaseCest
{
    use ServiceContainer; //we need service to compare with PayPal response

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->enableExpressButtons($I);
        $I->updateModuleConfiguration('oscPayPalShowProductDetailsButton', true);
    }

    public function expressCheckoutFromDetailsButton(AcceptanceTester $I): void
    {
        $I->wantToTest('checkout from details page with empty cart. Customer is logged in.
         Return to payment page after PP approval.');

        $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        //We have an empty cart at this time
        //NOTE: manually express checkout works if we have no sid cookie at this point,
        //      but codeception test did not have sid cookie at end of approveOrder call.
        //So for now, we test with a logged in customer
        $token = $this->approveExpressPayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);
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
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname']
            ]
        );
    }

    /**
     * @group oscpaypal_with_webhook_from_details
     * @group oscpaypal_with_webhook
     */
    public function expressCheckoutFromDetailsButtonWithWebhook(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page with empty cart. Customer is logged in to shop and uses 
            different PP login mail. Expect to see shop invoice as no address change done on PP side. 
            Test needs working webhook'
        );

        $this->assertPayPalBuyerNotExistsInShop($I);

        $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        //We have an empty cart at this time
        //NOTE: manually express checkout works if we have no sid cookie at this point,
        //      but codeception test did not have sid cookie at end of approveOrder call.
        //So for now, we test with a logged in customer
        $token = $this->approveExpressPayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->waitForPageLoad();

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        //wait for webhook to do its work
        $I->wait(120);
        $orderId = $this->assertOrderPaidAndFinished($I);

        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => Fixtures::get('details')['firstname']
            ]
        );
    }

    /**
     * @group oscpaypal_with_webhook_from_details
     * @group oscpaypal_with_webhook
     * @group oscpaypal_express_details_addresschange
     */
    public function expressCheckoutFromDetailsButtonWithShopDeliveryAddressChange(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page with empty cart. Customer is logged in to shop. Customer
             changes delivery address after return from PayPal.'
        );

        $this->assertPayPalBuyerNotExistsInShop($I);

        $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        //We have an empty cart at this time
        $token = $this->approveExpressPayPalTransaction($I, '&context=continue&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() . '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token);

        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->waitForPageLoad();

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);

        $I->dontSeeInDatabase(
            'oxaddress',
            [
                'OXFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        $this->submitOrderWithUpdatedDeliveryAddress($I);

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        //wait for webhook to do its work
        $I->wait(120);
        $orderId = $this->assertOrderPaidAndFinished($I);

        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => self::DELIVERY_FIRSTNAME,
                'OXDELCOMPANY' => self::DELIVERY_COMPANY,
                'OXDELADDINFO' => self::DELIVERY_OXADDINFO
            ]
        );

        $this->assertDeliveryAddress($I, $orderId);
    }

    public function expressCheckoutFromDetailsButtonAsGuest(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page from clean session and empty cart. ' .
                 'Customer is guest buyer without shop account.'
        );

        $this->assertPayPalBuyerNotExistsInShop($I);
        $payPalOrderId = $this->anonymousProceedToOrderStep($I);

        /** @var OrderCheckout $orderCheckout */
        $orderCheckout = new OrderCheckout($I);
        $orderCheckout->submitOrder();

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $payPalOrderId
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => $_ENV['sBuyerFirstName'],
                'OXDELFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        $I->seeInDatabase(
            'oxuser',
            [
                'OXUSERNAME' => $_ENV['sBuyerLogin']
            ]
        );
    }

    public function detailsButtonPlacementWithPrefilledCart(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page from clean session and filled cart.'
            . ' Customer is guest buyer without shop account.'
        );

        $this->assertPayPalBuyerNotExistsInShop($I);

        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I, '&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //button will not be shown on started paypal session
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->dontSeeElement("#PayPalButtonProductMain");

        $this->fromBasketToPayment($I);
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
                //the original 4 plus one from details
                'OXTOTALORDERSUM' => 5 * Fixtures::get('product')['bruttoprice_single'],
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        $I->seeInDatabase(
            'oxuser',
            [
                'OXUSERNAME' => $_ENV['sBuyerLogin']
            ]
        );
    }

    public function expressCheckoutFromDetailsAutomaticLogin(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page with empty cart. Customer is not logged in but has shop account ' .
            'with password.'
        );

        $this->setUserNameSameAsPayPal($I);
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', true);

        $I->seeInDatabase(
            'oxuser',
            [
                'OXUSERNAME' => $_ENV['sBuyerLogin']
            ]
        );

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveAnonymousPayPalTransaction($I, '&aid=' . Fixtures::get('product')['oxid']);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

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
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }

    /**
     * @group oscpaypal_express_details_addresschange
     */
    public function testExpressCheckoutFromDetailsButtonAsGuestChangeAddressInOrderStep(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'checkout from details page from clean session and empty cart. ' .
            'Customer is guest buyer without shop account. Address change on last order step.'
        );

        $this->assertPayPalBuyerNotExistsInShop($I);

        $paypalOrderId = $this->anonymousProceedToOrderStep($I);
        $this->submitOrderWithUpdatedDeliveryAddress($I);

        $thankYouPage = new ThankYou($I);
        $orderNumber = $thankYouPage->grabOrderNumber();
        $I->assertGreaterThan(1, $orderNumber);

        $orderId = $I->grabFromDatabase('oxorder', 'oxid', ['OXORDERNR' => $orderNumber]);
        $I->seeInDataBase(
            'oscpaypal_order',
            [
                'OXORDERID' => $orderId,
                'OXPAYPALORDERID' => $paypalOrderId
            ]
        );
        $I->seeInDataBase(
            'oxorder',
            [
                'OXID' => $orderId,
                'OXTOTALORDERSUM' => Fixtures::get('product')['one_item_total_with_shipping'],
                'OXBILLFNAME' => $_ENV['sBuyerFirstName'],
                'OXDELFNAME' => self::DELIVERY_FIRSTNAME,
                'OXDELCOMPANY' => self::DELIVERY_COMPANY,
                'OXDELADDINFO' => self::DELIVERY_OXADDINFO
            ]
        );

        $I->seeInDatabase(
            'oxuser',
            [
                'OXUSERNAME' => $_ENV['sBuyerLogin']
            ]
        );

        $this->assertDeliveryAddress($I, $orderId);
    }

    private function assertDeliveryAddress(
        AcceptanceTester $I,
        string $orderId
    ): void {
        $payPalOrderId = $I->grabFromDatabase(
            'oscpaypal_order',
            'OXPAYPALORDERID',
            [
                'oxorderid' => $orderId
            ]
        );

        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        /** @var PayPalOrder $payPalOrder */
        $payPalOrder = $paymentService->fetchOrderFields($payPalOrderId);
        $I->assertSame(
            self::DELIVERY_POSTALCODE,
            (string) $payPalOrder->purchase_units[0]->shipping->address->postal_code
        );
        $I->assertStringContainsString(
            self::DELIVERY_COMPANY,
            (string) $payPalOrder->purchase_units[0]->shipping->address->address_line_2
        );
        $I->assertStringContainsString(
            self::DELIVERY_OXADDINFO,
            (string) $payPalOrder->purchase_units[0]->shipping->address->address_line_2
        );
    }

    private function anonymousProceedToOrderStep(AcceptanceTester $I): string
    {
        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $I->dontSeeCookie('sid');
        $I->dontSeeCookie('sid_key');

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approvePayPalTransaction($I, '&aid=' . Fixtures::get('product')['oxid']);

        //shipping dropdown on PayPal page
        $I->seeElement('//button[@id="change-shipping"]');
        $I->click('//button[@id="change-shipping"]');
        $I->wait(1);
        $I->seeElement('//select[@id="shippingDropdown"]');

        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->waitForPageLoad();

        return $token;
    }

    private function assertPayPalBuyerNotExistsInShop(AcceptanceTester $I): void
    {
        $I->dontSeeInDatabase(
            'oxaddress',
            [
                'OXFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        $I->dontSeeInDatabase(
            'oxuser',
            [
                'OXUSERNAME' => $_ENV['sBuyerLogin']
            ]
        );
    }
}
