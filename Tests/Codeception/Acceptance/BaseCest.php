<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Facts\Facts;
use Codeception\Util\Fixtures;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalLogin;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Page\Checkout\ThankYou;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Page\Checkout\Basket as BasketCheckout;

abstract class BaseCest
{
    public const DELIVERY_COMPANY = 'VIP Company';
    public const DELIVERY_POSTALCODE = '22547';
    public const DELIVERY_FIRSTNAME = 'Paypaltester';
    public const DELIVERY_LASTNAME = 'Shoppingisfun';
    public const DELIVERY_OXADDINFO = 'some additional delivery info';

    public function _before(AcceptanceTester $I): void
    {
        $this->activateModules();

        $I->clearShopCache();
        $I->setPayPalBannersVisibility(false);
        $I->updateConfigInDatabase('blUseStock', false, 'bool');
        $I->updateConfigInDatabase('bl_perfLoadPrice', true, 'bool');
        $I->updateConfigInDatabase('iNewBasketItemMessage', false, 'bool');
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', false);

        $I->updateModuleConfiguration('oscPayPalSandboxClientId', $_ENV['oscPayPalSandboxClientId']);
        $I->updateModuleConfiguration('oscPayPalSandboxMode', true);
        $I->updateModuleConfiguration('oscPayPalSandboxClientSecret', $_ENV['oscPayPalSandboxClientSecret']);
        $I->updateModuleConfiguration('oscPayPalSandboxWebhookId', 'dummy_webhook_id');

        $this->ensureShopUserData($I);
        $this->enableExpressButtons($I);
        $this->enablePayments($I);
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->ensureShopUserData($I);
        $this->enableExpressButtons($I);
        $I->updateConfigInDatabase('blShowNetPrice', false, 'bool');
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', false);

        $I->deleteFromDatabase('oxaddress', ['OXFNAME' => $_ENV['sBuyerFirstName']]);
        $I->deleteFromDatabase('oxorder', ['OXORDERNR >=' => '2']);
        $I->deleteFromDatabase('oxuserbaskets', ['OXTITLE >=' => 'savedbasket']);
        $I->deleteFromDatabase('oscpaypal_order', ['OXSHOPID >' => '0']);
        $I->resetCookie('sid');
        $I->resetCookie('sid_key');
    }

    protected function getShopUrl(): string
    {
        $facts = new Facts();

        return $facts->getShopUrl();
    }

    /**
     * Activates modules
     */
    protected function activateModules(int $shopId = 1): void
    {
        $testConfig        = new \OxidEsales\TestingLibrary\TestConfig();
        $modulesToActivate = $testConfig->getModulesToActivate();

        if ($modulesToActivate) {
            $serviceCaller = new \OxidEsales\TestingLibrary\ServiceCaller();
            $serviceCaller->setParameter('modulestoactivate', $modulesToActivate);

            try {
                $serviceCaller->callService('ModuleInstaller', $shopId);
            } catch (ModuleSetupException $e) {
                // this may happen if the module is already active,
                // we can ignore this
            }
        }
    }

    protected function ensureShopUserData(AcceptanceTester $I): void
    {
        $toBeUpdated = $_ENV['sBuyerLogin'];
        if (0 < $I->grabNumRecords('oxuser', ['oxusername' => Fixtures::get('userName')])) {
            $toBeUpdated = Fixtures::get('userName');
            $I->deleteFromDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
        }
        if (0 < $I->grabNumRecords('oxnewssubscribed', ['oxemail' => $_ENV['sBuyerLogin']])) {
            $I->deleteFromDatabase('oxnewssubscribed', ['oxemail' => $_ENV['sBuyerLogin']]);
        }

        $I->updateInDatabase(
            'oxuser',
            [
                'oxusername' => Fixtures::get('userName'),
                'oxcity'     => Fixtures::get('details')['oxcity'],
                'oxstreet'   => Fixtures::get('details')['oxstreet'],
                'oxstreetnr' => Fixtures::get('details')['oxstreetnr'],
                'oxzip'      => Fixtures::get('details')['oxzip'],
                'oxfname'    => Fixtures::get('details')['firstname'],
                'oxlname'    => Fixtures::get('details')['lastname'],
            ],
            [
                'oxusername' => $toBeUpdated
            ]
        );
        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => '$2y$10$b186f117054b700a89de9uXDzfahkizUucitfPov3C2cwF5eit2M2',
                'oxpasssalt' => 'b186f117054b700a89de929ce90c6aef'
            ],
            [
                'oxusername' => Fixtures::get('userName')
            ]
        );

        $I->seeInDatabase('oxuser', ['oxusername' => Fixtures::get('userName')]);
        $I->dontSeeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
    }

    protected function setUserDataSameAsPayPal(AcceptanceTester $I, bool $removePassword = false): void
    {
        $I->updateInDatabase(
            'oxuser',
            [
                'oxusername' => $_ENV['sBuyerLogin'],
                'oxfname'    => $_ENV['sBuyerFirstName'],
                'oxlname'    => $_ENV['sBuyerLastName'],
                'oxstreet'   => 'ESpachstr.',
                'oxstreetnr' => '1',
                'oxzip'      => '79111',
                'oxcity'     => 'Freiburg'
            ],
            [
                'oxusername' => Fixtures::get('userName')
            ]
        );

        if ($removePassword) {
            $this->removePassword($I);
        }
    }

    protected function removePassword(AcceptanceTester $I): void
    {
        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => '',
                'oxpasssalt' => ''
            ],
            [
                'oxusername' => $_ENV['sBuyerLogin']
            ]
        );
    }

    protected function setUserNameSameAsPayPal(AcceptanceTester $I): void
    {
        $I->updateInDatabase(
            'oxuser',
            [
                'oxusername' => $_ENV['sBuyerLogin'],
            ],
            [
                'oxusername' => Fixtures::get('userName')
            ]
        );
    }

    protected function proceedToPaymentStep(
        AcceptanceTester $I,
        string $userName = null
    ): void {

        $userName = $userName ?: Fixtures::get('userName');

        $home = $I->openShop()
            ->loginUser($userName, Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        //add product to basket and start checkout
        $this->fillBasket($I);
        $this->fromBasketToPayment($I);
    }

    protected function fromBasketToPayment(AcceptanceTester $I): void
    {
        $I->amOnPage('/en/cart');
        $basketPage = new BasketCheckout($I);
        $basketPage->goToNextStep()
            ->goToNextStep();

        if (!$I->seePageHasElement("//a[contains(@href, 'fnc=cancelPayPalPayment')]")) {
            $I->see(Translator::translate('PAYMENT_METHOD'));
        }
    }

    protected function proceedToBasketStep(AcceptanceTester $I, string $userName = null, bool $logMeIn = true): void
    {
        $I->updateModuleConfiguration('oscPayPalShowCheckoutButton', true);

        $userName = $userName ?: Fixtures::get('userName');

        $home = $I->openShop();
        if ($logMeIn) {
            $home->loginUser($userName, Fixtures::get('userPassword'));
        }
        $I->waitForText(Translator::translate('HOME'));

        //add product to basket and start checkout
        $this->fillBasket($I);
        $I->seeElement("#PayPalPayButtonNextCart2");
    }

    protected function fillBasket(AcceptanceTester $I): void
    {
        //add product to basket and start checkout
        $product = Fixtures::get('product');
        $basket = new Basket($I);
        $basket->addProductToBasketAndOpenBasket($product['oxid'], $product['amount'], 'basket');
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));
    }

    protected function finalizeOrder(AcceptanceTester $I): string
    {
        $paymentPage = new PaymentCheckout($I);
        $paymentPage->goToNextStep()
            ->submitOrder();

        $payPalPage = new PayPalLogin($I);
        $payPalPage->confirmPayPal();

        $thankYouPage = new ThankYou($I);

        return  $thankYouPage->grabOrderNumber();
    }

    protected function finalizeOrderInOrderStep(AcceptanceTester $I): string
    {
        $orderPage = new OrderCheckout($I);
        $orderPage->submitOrder();

        $thankYouPage = new ThankYou($I);

        return  $thankYouPage->grabOrderNumber();
    }

    protected function approvePayPalTransaction(AcceptanceTester $I, string $addParams = ''): string
    {
        //workaround to approve the transaction on PayPal side
        $loginPage = new PayPalLogin($I);
        $loginPage->openPayPalApprovalPage($I, $addParams);
        $token = $loginPage->getToken();
        $loginPage->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        return $token;
    }

    protected function approveAnonymousPayPalTransaction(AcceptanceTester $I, string $addParams = ''): string
    {
        //workaround to approve the transaction on PayPal side
        $loginPage = new PayPalLogin($I);
        $loginPage->openPayPalApprovalPageAsAnonymousUser($I, $addParams);
        $token = $loginPage->getToken();
        $loginPage->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        return $token;
    }

    protected function openOrderPayPal(AcceptanceTester $I, string $orderNumber): void
    {
        $adminPanel = $I->loginAdmin();
        $orders = $adminPanel->openOrders();
        $I->waitForDocumentReadyState();
        $orders->find($orders->orderNumberInput, $orderNumber);

        $I->selectListFrame();
        $I->click(Translator::translate('tbclorder_oscpaypal'));
        $I->selectEditFrame();
    }

    protected function enableExpressButtons(AcceptanceTester $I, bool $flag = true): void
    {
        $I->updateModuleConfiguration('oscPayPalShowProductDetailsButton', $flag);
        $I->updateModuleConfiguration('oscPayPalShowBasketButton', $flag);
        $I->updateModuleConfiguration('oscPayPalShowCheckoutButton', $flag);
    }

    protected function checkWeAreStillInAdminPanel(AcceptanceTester $I): void
    {
        //we did not end up on shop start page
        $I->dontSee(Translator::translate('HOME'));
        $I->dontSee(Translator::translate('START_BARGAIN_HEADER'));
        $I->dontSee(Translator::translate('Maintenance mode'));
    }

    protected function enablePayments(AcceptanceTester $I): void
    {
        //we did not end up on shop start page
        $I->updateInDatabase('oxpayments', ['oxactive' => 1], ['oxid' => 'oscpaypal_pui']);
        $I->updateInDatabase('oxpayments', ['oxactive' => 1], ['oxid' => 'oscpaypal_sofort']);
        $I->updateInDatabase('oxpayments', ['oxactive' => 1], ['oxid' => 'oscpaypal_acdc']);
        $I->updateInDatabase('oxpayments', ['oxactive' => 1], ['oxid' => 'oscpaypal']);
    }

    protected function setProductAvailability(AcceptanceTester $I, int $stockflag, int $stock): void
    {
        $I->updateInDatabase(
            'oxarticles',
            [
                'oxstockflag' => $stockflag,
                'oxstock' => $stock
            ],
            [
                'oxid' => Fixtures::get('product')['oxid']
            ]
        );
    }

    protected function assertOrderPaidAndFinished(AcceptanceTester $I): string
    {
        $I->seeNumRecords(0, 'oxorder', ['oxordernr' => 0]);

        $orderId = $I->grabFromDatabase('oscpaypal_order', 'oxorderid');
        $transactionId = $I->grabFromDatabase('oscpaypal_order', 'oscpaypaltransactionid');

        $oxPaid = $I->grabFromDatabase('oxorder', 'oxpaid', ['OXID' => $orderId]);
        $I->assertStringStartsWith(date('Y-m-d'), $oxPaid);

        $transStatus = $I->grabFromDatabase('oxorder', 'oxtransstatus', ['OXID' => $orderId]);
        $I->assertStringStartsWith('OK', $transStatus);

        $transId = $I->grabFromDatabase('oxorder', 'oxtransid', ['OXID' => $orderId]);
        $I->assertEquals($transactionId, $transId);

        $I->seeNumRecords(1, 'oscpaypal_order');
        $I->seeNumRecords(1, 'oscpaypal_order', ['oscpaypalstatus' => 'COMPLETED']);

        return $orderId;
    }

    protected function submitOrderWithUpdatedDeliveryAddress(AcceptanceTester $I): void
    {
        /** @var UserCheckout $userCheckout */
        $userCheckout = (new OrderCheckout($I))->editUserAddress()
            ->openShippingAddressForm();
        $I->executeJS('document.getElementById("shippingAddressForm").style=""');
        $I->fillField('deladr[oxaddress__oxfname]', self::DELIVERY_FIRSTNAME);
        $I->fillField('deladr[oxaddress__oxlname]', self::DELIVERY_LASTNAME);
        $I->fillField("deladr[oxaddress__oxcompany]", self::DELIVERY_COMPANY);
        $I->fillField("deladr[oxaddress__oxaddinfo]", self::DELIVERY_OXADDINFO);
        $I->fillField("deladr[oxaddress__oxstreet]", "Meinestrasse");
        $I->fillField("deladr[oxaddress__oxstreetnr]", "10");
        $I->fillField("deladr[oxaddress__oxzip]", self::DELIVERY_POSTALCODE);
        $I->fillField("deladr[oxaddress__oxcity]", "Hamburg");
        $I->executeJS('document.getElementById("delCountrySelect").options[3].selected = true;');

        $userCheckout->goToNextStep()
            ->goToNextStep()
            ->submitOrder();
    }
}
