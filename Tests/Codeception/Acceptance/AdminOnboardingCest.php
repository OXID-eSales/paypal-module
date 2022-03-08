<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalAdmin;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\Filesystem\Filesystem;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\Page\PayPalLogin;

/**
 * All tests related to PayPal admin section go here.
 * Only superficial checks if controllers are accessible without errors.
 *
 * @group osc_paypal
 * @group osc_paypal_admin
 * @group osc_paypal_admin_onboarding
 */
final class AdminOnboardingCest extends BaseCest
{
    use ServiceContainer;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->updateModuleConfiguration('oscPayPalSandboxClientId', '');
        $I->updateModuleConfiguration('oscPayPalSandboxMode', false);
        $I->updateModuleConfiguration('oscPayPalSandboxClientSecret', '');
        $I->updateModuleConfiguration('oscPayPalSandboxWebhookId', '');

        $I->clearShopCache();
    }

    public function testOnboardingLinkIsShown(AcceptanceTester $I): void
    {
        $I->wantToTest('PayPal onboarding');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openConfiguration();
        $this->checkWeAreStillInAdminPanel($I);

        $I->seeElement('#opmode');
        $I->selectOption('#opmode', 'live');
        $I->seeElement('#paypalonboardingpopuplive');
        $I->click('#paypalonboardingpopuplive');
        $I->switchToLastWindow();

        $I->seeElement('#paypalonboardinglive');
        $link = $I->grabAttributeFrom('#paypalonboardinglive', 'href');
        $I->assertStringContainsString('partnerClientId', $link);
    }

    public function testOnboardingSandboxMode(AcceptanceTester $I): void
    {
        $I->wantToTest('connect my PayPal sandbox business account with my shop');

        $I->loginAdmin();
        $adminPanel = new PayPalAdmin($I);
        $adminPanel->openConfiguration();
        $this->checkWeAreStillInAdminPanel($I);

        $I->see(substr(Translator::translate('OSC_PAYPAL_ERR_CONF_INVALID'), 0, 65));
        $I->seeElement('#opmode');
        $I->selectOption('#opmode', 'sandbox');
        $I->seeElement('#paypalonboardingpopupsandbox');
        $I->assertEmpty($I->grabAttributeFrom("#client-sandbox-id", 'value'));

        $I->click('#paypalonboardingpopupsandbox');
        $I->switchToLastWindow();

        $I->seeElement('#paypalonboardingsandbox');
        $I->click('#paypalonboardingsandbox');
        $I->switchToLastWindow();

        $payPal = new PayPalLogin($I);
        $payPal->loginToPayPalOnboarding($_ENV['sSellerLogin'], $_ENV['sSellerPassword']);
        $I->seeElement($payPal->agreeConnectButton);
        $I->click($payPal->agreeConnectButton);
        $I->waitForPageLoad();

        $I->waitForElement("//a[contains(@href, 'cl=oscpaypalonboarding')]", 20);
        $I->waitForElementClickable("//a[contains(@href, 'cl=oscpaypalonboarding')]");
        $href = $I->grabAttributeFrom("//a[contains(@href, 'cl=oscpaypalonboarding')]", 'href');
        $I->assertStringContainsString('returnFromSignup', $href);
        $I->amOnUrl($href);
        $I->see('You can now close the window');

        //NOTE: in case of non ssl url, the webhook cannot be created, so webhook part depends on test environment
        //locally we will still see a note, that the module is inactive
        $I->switchToWindow();
        $I->reloadPage();

        $I->assertNotEmpty($I->grabAttributeFrom("#client-sandbox-id", 'value'));
    }
}
