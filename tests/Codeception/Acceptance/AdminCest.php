<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalAdmin;

/**
 * All tests related to PayPal admin section go here.
 * Only superficial checks if controllers are accessible without errors.
 *
 * @group osc_paypal
 * @group osc_paypal_admin
 * @group osc_paypal_admin_config
 */
final class AdminCest extends BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->clearShopCache();
    }

    public function testPayPalAdminConfiguration(AcceptanceTester $I): void
    {
        $I->wantToTest('that shop admin PayPal configuration section can be loaded without error');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openConfiguration();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(Translator::translate('OSC_PAYPAL_CREDENTIALS'));
        $I->see(Translator::translate('OSC_PAYPAL_OPMODE_SANDBOX'));
    }

    public function testPayPalAdmin3DConfiguration(AcceptanceTester $I): void
    {
        $I->wantToTest('that shop admin PayPal configuration section for 3Ds is shown');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openConfiguration();
        $this->checkWeAreStillInAdminPanel($I);

        $I->retryClick('#heading5');
        $I->waitForElement("//select[@id='sca-config']");
        $I->see(Translator::translate('OSC_PAYPAL_SCA_CONTINGENCY_LABEL'));
    }

    public function testPayPalAdminTransactions(AcceptanceTester $I): void
    {
        $I->markTestIncomplete('TODO: enable feature and test in a later module version');

        $I->wantToTest('that shop admin PayPal transactions section can be loaded without error');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openTransactions();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->see(Translator::translate('OSC_PAYPAL_ACCOUNT_ID'));
        $I->see(Translator::translate('OSC_PAYPAL_TRANSACTION_ID'));

        $I->click(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->see(Translator::translate('OSC_PAYPAL_STORE_ID'));
        $I->dontSee(Translator::translate('OSC_PAYPAL_ERROR'));
    }

    public function testPayPalAdminBalances(AcceptanceTester $I): void
    {
        $I->markTestIncomplete('TODO: enable feature and test in a later module version');

        $I->wantToTest('that shop admin PayPal balances section can be loaded without error');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openBalances();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->click(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->see(Translator::translate('OSC_PAYPAL_CURRENCY_CODE'));
        $I->dontSee(Translator::translate('OSC_PAYPAL_ERROR'));
    }

    public function testPayPalSubscriptions(AcceptanceTester $I): void
    {
        $I->markTestIncomplete('TODO: enable feature and test in a later module version');

        $I->wantToTest('that shop admin PayPal subscriptions section can be loaded without error');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openSubscriptions();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->click(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->see(Translator::translate('OSC_PAYPAL_SUBSCRIPTION_PLAN_ID'));
    }

    public function testPayPalDispute(AcceptanceTester $I): void
    {
        $I->markTestIncomplete('TODO: enable feature and test in a later module version');

        $I->wantToTest('that shop admin PayPal dispute section can be loaded without error');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openDisputes();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->click(Translator::translate('OSC_PAYPAL_FILTER'));
        $I->see(Translator::translate('OSC_PAYPAL_TRANSACTION_ID'));
        $I->see(Translator::translate('OSC_PAYPAL_DISPUTE_STATE'));

        $I->markTestIncomplete('TODO: figure out why this error is shown');
        $I->dontSee(Translator::translate('OSC_PAYPAL_ERROR'));
    }

    public function checkTransactionInAdminForNonPayPalOrder(AcceptanceTester $I)
    {
        $I->wantToTest('seeing PayPal transactions in admin order for non PayPal order');

        $this->openOrderPayPal($I, '1');
        $I->see(Translator::translate('OSC_PAYPAL_ERROR_NOT_PAID_WITH_PAYPAL'));
    }
}
