<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use Codeception\Example;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Checkout\UserCheckout;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidEsales\Codeception\Page\Home;

/**
 * @group osc_paypal
 * @group osc_paypal_checkout_express
 * @group osc_paypal_checkout_paypallogin
 * @group osc_paypal_remote_login
 */
final class PayPalCheckoutLoginCest extends BaseCest
{
    public function checkoutWithPaypalFromBasketStepAutomaticLogin(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'automatic login as existing but not logged in shop user. Shop login and PayPal login mail are the same.'
        );

        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', true);

        $this->setUserDataSameAsPayPal($I);
        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //PayPal logs us in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
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
                'OXTOTALORDERSUM' => '119.6'
            ]
        );
    }

    public function checkoutWithPaypalFromBasketStepNoAutomaticLoginFinalizeAsSameUser(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'no automatic login as existing but not logged in shop user. Shop login and PayPal login mail are the same.'
        );

        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', false);

        $token = $this->startExpressCheckoutAsNotLoggedInExistingUser($I);

        //finalize order with logging in same user as paypal email
        $home = new Home($I);
        $home->loginUser($_ENV['sBuyerLogin'], Fixtures::get('userPassword'));
        $I->dontSee(Translator::translate('OSC_PAYPAL_LOG_IN_TO_CONTINUE'));

        $page = new UserCheckout($I);
        $page->goToNextStep();
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $orderNumber = $this->finalizeOrder($I);

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
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }

    public function checkoutWithPaypalFromBasketStepNoAutomaticLoginFinalizeAsDifferentUser(AcceptanceTester $I): void
    {
        $I->wantToTest(
            'no automatic login as existing but not logged in shop user. Log into shop with different account.'
        );

        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', false);

        $token = $this->startExpressCheckoutAsNotLoggedInExistingUser($I);

        //finalize order with logging in different user than paypal email
        $home = new Home($I);
        $home->loginUser(Fixtures::get('defaultUserName'), Fixtures::get('userPassword'));
        $I->dontSee(Translator::translate('OSC_PAYPAL_LOG_IN_TO_CONTINUE'));

        $page = new UserCheckout($I);
        $page->goToNextStep();
        $I->see(Translator::translate('OSC_PAYPAL_PAY_PROCESSED'));

        $orderNumber = $this->finalizeOrder($I);

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
                'OXBILLFNAME' => Fixtures::get('defaultUserFirstName')
            ]
        );
    }

    /**
     * @dataProvider providerLogInWithPayPal
     */
    public function checkoutWithPaypalExpressNewCustomer(AcceptanceTester $I, Example $example): void
    {
        $I->wantToTest(
            'checking out with PayPal payment as not existing shop user will end me up with passwordless shop login, '
            . ' PPlogin is '
            . (int) $example['oscPayPalLoginWithPayPalEMail']
        );

        //login flag does not make a difference in this case
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', $example['oscPayPalLoginWithPayPalEMail']);

        //user does not exist in database
        $I->seeInDatabase('oxuser', ['oxusername' => Fixtures::get('userName')]);
        $I->dontSeeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //new user without password was created
        $I->seeInDatabase('oxuser', ['oxusername' => Fixtures::get('userName')]);
        $I->seeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
        $I->seeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin'], 'oxpassword' => '']);

        //PayPal logs us in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
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
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }

    /**
     * @dataProvider providerLogInWithPayPal
     */
    public function checkoutWithPaypalExpressRepeatGuestBuySameAddress(AcceptanceTester $I, Example $example): void
    {
        $I->wantToTest(
            'returning passwordless shop login customer, PPlogin is '
            . (int) $example['oscPayPalLoginWithPayPalEMail']
        );

        //login flag should not make a difference in this case as we only have a guest account anyway
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', $example['oscPayPalLoginWithPayPalEMail']);

        //paypal user does with same name and invoice exist in database but has no password
        $this->setUserDataSameAsPayPal($I, true);
        $I->seeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin'], 'oxpassword' => '']);

        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //PayPal logs us in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
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
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );
    }

    /**
     * @dataProvider providerLogInWithPayPal
     */
    public function checkoutWithPaypalExpressRepeatGuestBuyDifferentAddress(AcceptanceTester $I, Example $example): void
    {
        $I->wantToTest(
            'passwordless guest user, shop and PayPal email are same, invoice and names different, PPlogin is '
            . (int) $example['oscPayPalLoginWithPayPalEMail']
        );

        //login flag should not make a difference in this case as we only have a guest account anyway
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', $example['oscPayPalLoginWithPayPalEMail']);

        //paypal user does exist in database but has no password.
        //Shop name and invoice address is different from paypal, only email is the same
        $this->setUserNameSameAsPayPal($I);
        $this->removePassword($I);
        $I->seeInDatabase(
            'oxuser',
            [
                'oxusername' => $_ENV['sBuyerLogin'],
                'oxfname' => Fixtures::get('details')['firstname']
            ]
        );

        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //PayPal logs us in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
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
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => $_ENV['sBuyerFirstName']
            ]
        );

        $I->seeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin'], 'oxfname' => $_ENV['sBuyerFirstName']]);
    }

    /**
     * @dataProvider providerLogInWithPayPal
     */
    public function checkoutWithPaypalFromBasketAlreadyLoggedIn(AcceptanceTester $I, Example $example): void
    {
        $I->wantToTest(
            'Logged in shop user, shop and PayPal email and address are different, PPlogin is '
                . (int) $example['oscPayPalLoginWithPayPalEMail']
        );

        //setting should not make a difference, user is already logged in
        $I->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', $example['oscPayPalLoginWithPayPalEMail']);

        $this->proceedToBasketStep($I);

        //verify we are logged in
        $I->see(Translator::translate('MY_ACCOUNT'));
        $I->dontSee(Translator::translate('LOGIN'));
        $I->seeInDatabase('oxuser', ['oxusername' => Fixtures::get('userName')]);
        $I->dontseeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //We are already logged in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->dontSee(sprintf(Translator::translate('ERROR_MESSAGE_USER_USEREXISTS'), $_ENV['sBuyerLogin']));
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
                'OXTOTALORDERSUM' => '119.6',
                'OXBILLFNAME' => Fixtures::get('details')['firstname'],
                'OXDELFNAME' => Fixtures::get('details')['firstname']
            ]
        );

        $I->seeInDatabase(
            'oxuser',
            [
                'oxusername' => Fixtures::get('userName'),
                'oxfname' => Fixtures::get('details')['firstname']
            ]
        );
        $I->dontseeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
    }

    protected function providerLogInWithPayPal(): array
    {
        return [
            ['oscPayPalLoginWithPayPalEMail' => false],
            ['oscPayPalLoginWithPayPalEMail' => true]
        ];
    }

    protected function startExpressCheckoutAsNotLoggedInExistingUser(
        AcceptanceTester $I,
        bool $removePassword = false
    ): string {
        $this->setUserDataSameAsPayPal($I, $removePassword);
        $I->seeInDatabase('oxuser', ['oxusername' => $_ENV['sBuyerLogin']]);
        $I->dontSeeInDatabase('oxuser', ['oxusername' => Fixtures::get('userName')]);

        $this->proceedToBasketStep($I, $_ENV['sBuyerLogin'], false);

        $stoken = $I->grabValueFrom('//input[@name="stoken"]');
        $token = $this->approveExpressPayPalTransaction($I);
        $I->amOnUrl($this->getShopUrl() .
                    '?cl=oscpaypalproxy&fnc=approveOrder&orderID=' . $token .
                    '&stoken=' . $stoken);

        //PayPal logs us in and we skipped the address and payment step
        //pretend we are back in shop after clicking PayPal button and approving order
        $I->amOnUrl($this->getShopUrl() . '?cl=order');
        $I->waitForPageLoad();

        //we already have an account with password, we are just not logged in
        $I->see(Translator::translate('OSC_PAYPAL_LOG_IN_TO_CONTINUE'));
        $I->dontSee(sprintf(Translator::translate('ERROR_MESSAGE_USER_USEREXISTS'), $_ENV['sBuyerLogin']));
        $I->dontSee(Translator::translate('MY_ACCOUNT'));
        $I->see(Translator::translate('LOGIN'));
        $I->see(Translator::translate('PURCHASE_WITHOUT_REGISTRATION'));

        return $token;
    }
}
