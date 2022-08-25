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
 * @group osc_paypal_checkout_buttons
 */
final class ButtonPlacementCest extends BaseCest
{
    public function testDetailsButtonPlacement(AcceptanceTester $I): void
    {
        $I->wantToTest('details page express button placement');

        //all buttons disabled
        $this->enableExpressButtons($I, false);
        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->dontSeeElement("#PayPalButtonProductMain");

        //switch it on
        $I->updateModuleConfiguration('oscPayPalShowProductDetailsButton', true);

        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);
        $I->seeElement("#PayPalButtonProductMain");
    }

    public function testBasketButtonPlacement(AcceptanceTester $I): void
    {
        $I->wantToTest('basket page express button placement');

        //all buttons disabled
        $this->enableExpressButtons($I, false);
        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $this->fillBasket($I);
        $I->dontSeeElement("#PayPalPayButtonNextCart2");

        //switch it on
        $I->updateModuleConfiguration('oscPayPalShowBasketButton', true);
        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $this->fillBasket($I);
        $I->seeElement("#PayPalPayButtonNextCart2");
    }

    public function testCheckoutButtonPlacement(AcceptanceTester $I): void
    {
        $I->wantToTest('payment page express button placement. Needs a logged in user.');

        //all buttons disabled
        $this->enableExpressButtons($I, false);
        $this->proceedToPaymentStep($I, Fixtures::get('userName'));
        $I->dontSeeElement("#PayPalButtonPaymentPage");

        //switch it on
        $I->updateModuleConfiguration('oscPayPalShowCheckoutButton', true);
        $I->clearShopCache();
        $this->proceedToPaymentStep($I, Fixtures::get('userName'));
        $I->seeElement("#PayPalButtonPaymentPage");
    }
}
