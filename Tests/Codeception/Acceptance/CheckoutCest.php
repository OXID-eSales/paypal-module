
3
Module PayPal
Software project
New
PLANNING
DEVELOPMENT
OPERATIONS
You're in a company-managed project

Projects
Module PayPal
PS - PayPal Module

0 days remaining
KW 2 + 3 (2022)

Show tickets assigned to

To Do
In Progress
Ready for Review
Review
Done

PSPAYPAL-496Open3 sub-tasksIntegration uAPM (unbranded Alternative Payment Methods)

uAPM-API-Calls im Checkout
Technical taskTrivial priority3d
Assignee: Mario Lorenz
PSPAYPAL503-
uAPMs müssen als Zahlungsarten bei der Modulinstallation angelegt werden
Technical taskTrivial priority1d
Assignee: Johannes Ackermann
PSPAYPAL501-
Alte Button-Steuerung muss zurückgebaut werden
Technical taskTrivial priority1.25d
Assignee: Mario Lorenz
PSPAYPAL502-

PSPAYPAL-516Open10 sub-tasksosc-paypal module

Create CI job for client
Technical taskTrivial priority-
PSPAYPAL522-
Create tests for client
Technical taskTrivial priority-
PSPAYPAL523-
Create integration tests for the module
Technical taskTrivial priority-
PSPAYPAL524-
Refactor webhook controller to extend from widgetcontroller.
Technical taskTrivial priority-
PSPAYPAL525-
TBD: own logger for PayPal module (or at least some common prefix)
Technical taskTrivial priority-
PSPAYPAL526-
Fix basket fraud issue
Technical taskTrivial priority-
PSPAYPAL527-
transfer module code from ps git to github
Technical taskTrivial priority-
Assignee: Heike Reuter
PSPAYPAL517-
transfer paypal client code from ps git to github
Technical taskTrivial priority-
Assignee: Heike Reuter
PSPAYPAL518-
move client code generation from ps git to private github repo
Technical taskTrivial priority-
Assignee: Heike Reuter
PSPAYPAL519-
create CI job for module
Technical taskTrivial priority-
Assignee: Mario Lorenz
PSPAYPAL520-

Other Issues2 issues

Login während PayPal-Kauf
StoryTrivial priority-
Assignee: Mario Lorenz
PSPAYPAL515-
Update Wiki Main-Page
StoryTrivial priority-
Assignee: Mario Lorenz
PSPAYPAL521-

PSPAYPAL-516

PSPAYPAL-527
Fix basket fraud issue
Description

Module will capture approved costs, not basket costs at finalizeOrder. If (logged in) user puts a product to cart and approved payment with paypal, then opens shopping cart in a second tab and raises amount of products (or adds different products), then returns to first tab and finalizes the order, he will pay only authorized amount. The order will then be marked as paid and may contain totally different (way more expensive) products.

Got a codeception test reproducing this issue attached here, will not pushi t to github unless module is in private repo. Same issue can be reproduced with PayOne module btw.

TODO: test with guest buyer
Environment
None
Attachments (1)

CheckoutCest.php
19 Jan 2022, 06:13 PM
Activity
Show:
Pro tip: press M to comment
Details
Assignee
Unassigned
Reporter
Heike Reuter
Development
Labels
None
Refered RQ version
None
Teams
None
Original estimate
0d
Sprint
KW 2 + 3 (2022)
Priority
Trivial
Automation
Rule executions
More fields
Time tracking, Components, Fix versions, Affects versions, Due date
Created 8 days ago
Updated 8 days ago
Configure
CheckoutCest.php
php · 6 KB
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

        $this->proceedToPaymentStep($I);
        $token = $this->approvalPayPalTransaction($I);

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
                'OXTOTALORDERSUM' => '119.6'
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

    public function checkTransactionInAdminForNonPayPalOrder(AcceptanceTester $I)
    {
        $I->wantToTest('seeing PayPal transactions in admin order for non PayPal order');

        $this->openOrderPayPal($I, '1');
        $I->see(Translator::translate('OSC_PAYPAL_ERROR_NOT_PAID_WITH_PAYPAL'));
    }

    public function changeBasketDuringCheckout(AcceptanceTester $I)
    {
        $I->wantToTest('changing baskte contents after payment was authorized');

        $this->proceedToPaymentStep($I);
        $token = $this->approvalPayPalTransaction($I);

        //open new tab
        $I->openNewTab();
        $I->switchToNextTab();
        $I->amOnUrl($this->getShopUrl() . '/en/cart');

        $product = Fixtures::get('product');
        $basket = new Basket($I);
        $basket->addProductToBasketAndOpenBasket($product['oxid'], $product['amount'], 'basket');

        //finalize order in previous tab
        $I->switchToPreviousTab();
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

    private function proceedToPaymentStep(AcceptanceTester $I): void
    {
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
    }

    private function finalizeOrder(AcceptanceTester $I): string
    {
        $paymentPage = new PaymentCheckout($I);
        $paymentPage->goToNextStep()
            ->submitOrder();

        $thankYouPage = new ThankYou($I);

        return  $thankYouPage->grabOrderNumber();
    }

    private function approvalPayPalTransaction(AcceptanceTester $I): string
    {
        //workaround to approve the transaction on PayPal side
        $loginPage = new PayPalLogin($I);
        $loginPage->openPayPalApprovalPage($I);
        $token = $loginPage->getToken();
        $loginPage->approveStandardPayPal($_ENV['sBuyerLogin'], $_ENV['sBuyerPassword']);

        return $token;
    }

    private function openOrderPayPal(AcceptanceTester $I, string $orderNumber): void
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
