<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Step\Basket;
use OxidEsales\Codeception\Step\ProductNavigation;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;

/**
 * @group osc_paypal
 * @group osc_paypal_banners
 */
final class InstallmentBannersCest extends BaseCest
{
    public function _after(AcceptanceTester $I): void
    {
        $I->setPayPalBannersVisibility(true);
        $I->setPayPalBannersFlowSelectors();

        parent::_after($I);
    }

    public function shopStartPageLoads(AcceptanceTester $I):void
    {
        $I->wantToTest('shop start page loads with activated module and deactivated banners');

        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);

        $I->waitForText(Translator::translate('HOME'));
        $I->waitForText(Translator::translate('START_BARGAIN_HEADER'));
        $I->dontSeePayPalInstallmentBanner();
    }

    public function shopStartPageShowsBanner(AcceptanceTester $I): void
    {
        $I->wantToTest('shop start page with installment banner');

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateModuleConfiguration('oscPayPalBannersStartPage', true);

        $I->openShop();
        $I->waitForText(Translator::translate('HOME'));

        $I->seePayPalInstallmentBanner();
    }

    public function categoryPageShowsBanner(AcceptanceTester $I): void
    {
        $I->wantToTest('category page with installment banner');

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateModuleConfiguration('oscPayPalBannersCategoryPage', true);

        $home = $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $I->dontSeePayPalInstallmentBanner(); //no banner on home page activted

        $home->openCategoryPage("Kiteboarding");
        $I->seePayPalInstallmentBanner();
    }

    public function searchResultsPageShowsBanner(AcceptanceTester $I): void
    {
        $I->wantToTest('search resuotes page with installment banner');

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateModuleConfiguration('oscPayPalBannersSearchResultsPage', true);

        $home = $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $I->dontSeePayPalInstallmentBanner(); //no banner on home page activted

        $home->openCategoryPage("Kiteboarding");
        $I->dontSeePayPalInstallmentBanner(); //no banner on category page activted

        $home->searchFor('Kite');
        $I->seePayPalInstallmentBanner();
    }

    public function detailPageShowsBanner(AcceptanceTester $I): void
    {
        $I->wantToTest('product details page with installment banner');

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateModuleConfiguration('oscPayPalBannersProductDetailsPage', true);

        $home = $I->openShop();
        $I->waitForText(Translator::translate('HOME'));
        $I->dontSeePayPalInstallmentBanner(); //no banner on home page activted

        $home->openCategoryPage("Kiteboarding");
        $I->dontSeePayPalInstallmentBanner(); //no banner on category page activted

        $home->searchFor('Kite');
        $I->dontSeePayPalInstallmentBanner(); //no banner on search results page activted

        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage(Fixtures::get('product')['oxid']);

        $I->seePayPalInstallmentBanner();
        $I->see(Fixtures::get('product')['oxtitle_1']);
        $I->see(Fixtures::get('product')['shortdesc_1']);
    }

    public function checkoutPageShowsBanner(AcceptanceTester $I): void
    {
        $I->wantToTest('checkout page with installment banner and logged in user');

        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateModuleConfiguration('oscPayPalBannersCheckoutPage', true);

        $home = $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));
        $I->dontSeePayPalInstallmentBanner(); //no banner on home page activted

        $home->openCategoryPage("Kiteboarding");
        $I->dontSeePayPalInstallmentBanner(); //no banner on category page activted

        $home->searchFor('Kite');
        $I->dontSeePayPalInstallmentBanner(); //no banner on search results page activted

        $product = Fixtures::get('product');
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage($product['oxid']);
        $I->dontSeePayPalInstallmentBanner(); //no banner on product details page

        $basket = new Basket($I);
        $checkout = $basket->addProductToBasketAndOpen($product['oxid'], $product['amount'], 'basket');
        $I->seePayPalInstallmentBanner();
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));

        $checkout->goToNextStep();
        $checkout->goToNextStep();
        $I->seePayPalInstallmentBanner();
    }

    public function disableAllBannersFlag(AcceptanceTester $I): void
    {
        $I->wantToTest('one flag to disable all banners');

        //all flags set to true
        $I->setPayPalBannersVisibility(true);
        $this->checkBannersOnAllPages($I, true);

        //disable main flag
        $I->updateModuleConfiguration('oscPayPalBannersShowAll', false);
        $this->checkBannersOnAllPages($I, false);

        //reenable main flag but change performance shop config flag
        $I->updateModuleConfiguration('oscPayPalBannersShowAll', true);
        $I->updateConfigInDatabase('bl_perfLoadPrice', false, 'bool');
        $this->checkBannersOnAllPages($I, false);

         //for paranoia's sake, enable performance flag again
        $I->updateConfigInDatabase('bl_perfLoadPrice', true, 'bool');
        $this->checkBannersOnAllPages($I, true);

        //verify that shop survives wrong selectors
        $I->setPayPalBannersCustomSelectors('invalid');
        $this->checkBannersOnAllPages($I, false);

        //verify that shop survives empty selectors
        $I->setPayPalBannersCustomSelectors('');
        $this->checkBannersOnAllPages($I, false);

        //paranoia back ton normal check
        $I->setPayPalBannersFlowSelectors();
        $this->checkBannersOnAllPages($I, true);
    }

    public function bannersMessageInBruttoMode(AcceptanceTester $I): void
    {
        $I->wantToTest('shop in brutto mode banner with filled cart and variants');

        $I->setPayPalBannersVisibility(true);

        $parentProduct = Fixtures::get('parent');
        $variant = Fixtures::get('variant');
        $alternateVariant = Fixtures::get('alternate_variant');
        $product = Fixtures::get('product');

        // Check banner amount when basket is not empty
        $basket = new Basket($I);
        $checkout = $basket->addProductToBasketAndOpen($product['oxid'], $product['amount'], 'basket');
        $I->seePayPalInstallmentBanner();
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));
        $I->checkInstallmentBannerData($product['bruttoprice_cart']);

        $parentProductNavigation = new ProductNavigation($I);
        $parentProductNavigation->openProductDetailsPage($parentProduct['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['bruttoprice_cart'] + $parentProduct['minBruttoPrice']);

        // Check banner amount when the given product is also in the basket
        $basket->addProductToBasket($variant['id'], 1);
        $I->waitForPageLoad();
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['bruttoprice_cart'] + $variant['bruttoprice']);

        //check banner in case we open variant parent details page and have no variant selected
        $parentProductNavigation->openProductDetailsPage($parentProduct['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['bruttoprice_cart'] + $variant['bruttoprice']); //check on details page

        //check banner in case we open alternate variant details page, alternate variant price should be added to price
        $parentProductNavigation->openProductDetailsPage($alternateVariant['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData(
            $product['bruttoprice_cart'] + $variant['bruttoprice'] + $alternateVariant['bruttoprice']
        ); //check on details page

        //check banner in case we open variant details page
        $parentProductNavigation->openProductDetailsPage($variant['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['bruttoprice_cart'] + $variant['bruttoprice']); //check on details page
    }

    public function bannersMessageInNettoMode(AcceptanceTester $I): void
    {
        $I->wantToTest('shop in netto mode banner with filled cart and variants');

        $I->setPayPalBannersVisibility(true);
        $I->updateConfigInDatabase('blShowNetPrice', true, 'bool');

        $parentProduct = Fixtures::get('parent');
        $variant = Fixtures::get('variant');
        $alternateVariant = Fixtures::get('alternate_variant');
        $product = Fixtures::get('product');

        // Check banner amount when basket is not empty
        $basket = new Basket($I);
        $basket->addProductToBasket($product['oxid'], $product['amount'], 'basket');
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['nettoprice_cart']); //check on start page

        $parentProductNavigation = new ProductNavigation($I);
        $parentProductNavigation->openProductDetailsPage($parentProduct['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['nettoprice_cart'] + $parentProduct['minNettoPrice']);

        // Check banner amount when the given product is also in the basket
        $basket->addProductToBasket($variant['id'], 1);
        $I->waitForPageLoad();
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['nettoprice_cart'] + $variant['nettoprice']);

        //check banner in case we open variant parent details page and have no variant selected
        $parentProductNavigation->openProductDetailsPage($parentProduct['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['nettoprice_cart'] + $variant['nettoprice']); //check on details page

        //check banner in case we open alternate variant details page, alternate variant price should be added to price
        $parentProductNavigation->openProductDetailsPage($alternateVariant['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData(
            $product['nettoprice_cart'] + $variant['nettoprice'] + $alternateVariant['nettoprice']
        ); //check on details page

        //check banner in case we open variant details page
        $parentProductNavigation->openProductDetailsPage($variant['id'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->seePayPalInstallmentBanner();
        $I->checkInstallmentBannerData($product['nettoprice_cart'] + $variant['nettoprice']); //check on details page
    }

    private function checkBannersOnAllPages($I, bool $visible = true)
    {
        $home = $I->openShop()
            ->loginUser(Fixtures::get('userName'), Fixtures::get('userPassword'));
        $I->waitForText(Translator::translate('HOME'));

        $methodName = $visible ? 'seePayPalInstallmentBanner' : 'dontSeePayPalInstallmentBanner';

        $I->$methodName(); //no banner on home page activted

        $home->openCategoryPage("Kiteboarding")
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->$methodName();

        $home->searchFor('Kite');
        $I->$methodName();

        $product = Fixtures::get('product');
        $productNavigation = new ProductNavigation($I);
        $productNavigation->openProductDetailsPage($product['oxid'])
            ->seeOnBreadCrumb(Translator::translate('YOU_ARE_HERE'));
        $I->$methodName();

        $basket = new Basket($I);
        $checkout = $basket->addProductToBasketAndOpen($product['oxid'], $product['amount'], 'basket');
        $I->see(Translator::translate('CONTINUE_TO_NEXT_STEP'));
        $I->$methodName();

        $checkout->goToNextStep();
        $checkout->goToNextStep();
        $I->$methodName();

        $home->logoutUser();
    }
}
