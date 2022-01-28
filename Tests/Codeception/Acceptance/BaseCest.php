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
    public function _before(AcceptanceTester $I): void
    {
        $this->activateModules();
    }

    public function _after(AcceptanceTester $I): void
    {
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
        $I->updateInDatabase(
            'oxuser',
            [
                'oxusername' => Fixtures::get('userName'),
                'oxcity'     => 'Freiburg',
                'oxstreet'   => 'Hauptstr.',
                'oxstreetnr' => '13',
                'oxzip'      => '79098',
                'oxfname'    => 'Marc',
                'oxlname'    => 'Muster'
            ],
            [
                'oxusername' => $_ENV['sBuyerLogin']
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
    }

    protected function setUserDataSameAsPayPal(AcceptanceTester $I): void
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
    }

    protected function proceedToPaymentStep(AcceptanceTester $I, string $userName = null): void
    {
        $I->updateModuleConfiguration('blPayPalShowCheckoutButton', true);

        $userName = $userName ?: Fixtures::get('userName');

        $home = $I->openShop()
            ->loginUser($userName, Fixtures::get('userPassword'));
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
    }

    protected function proceedToBasketStep(AcceptanceTester $I, string $userName = null, bool $logMeIn = true): void
    {
        $I->updateModuleConfiguration('blPayPalShowCheckoutButton', true);

        $userName = $userName ?: Fixtures::get('userName');

        $home = $I->openShop();
        if ($logMeIn) {
            $home->loginUser($userName, Fixtures::get('userPassword'));
        }
        $I->waitForText(Translator::translate('HOME'));

        //add product to basket and start checkout
        $product = Fixtures::get('product');
        $basket = new Basket($I);
        $basket->addProductToBasketAndOpenBasket($product['oxid'], $product['amount'], 'basket');
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));
        $I->seeElement("#PayPalPayButtonNextCart2");
    }

    protected function finalizeOrder(AcceptanceTester $I): string
    {
        $paymentPage = new PaymentCheckout($I);
        $paymentPage->goToNextStep()
            ->submitOrder();

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

    protected function approvePayPalTransaction(AcceptanceTester $I): string
    {
        //workaround to approve the transaction on PayPal side
        $loginPage = new PayPalLogin($I);
        $loginPage->openPayPalApprovalPage($I);
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
        $I->click(Translator::translate('tbclorder_paypal'));
        $I->selectEditFrame();
    }
}
