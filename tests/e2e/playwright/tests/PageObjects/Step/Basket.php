<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

use Codeception\Actor;
use OxidEsales\Codeception\Page\Checkout\UserCheckout;
use OxidEsales\Codeception\Page\Checkout\Basket as BasketPage;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\Header\MiniBasket;
use OxidEsales\Codeception\Page\Component\Modal;

/**
 * Class Basket
 * @package OxidEsales\Codeception\Step
 */
class Basket extends Step
{
    use MiniBasket;
    use Modal;

    /**
     * Add product to the basket without redirection
     *
     * @param string $productId The id of the product.
     * @param int    $amount    The amount of the product.
     */
    public function addProductToBasket(string $productId, int $amount)
    {
        $I = $this->user;
        //add Product to basket
        $params['fnc'] = 'tobasket';
        $params['aid'] = $productId;
        $params['am'] = $amount;
        $params['anid'] = $productId;

        $this->openPage($I, $params);
        $I->waitForElement($this->miniBasketMenuElement);
        $I->waitForPageLoad();
    }

    /**
     * Add product to the basket and open Basket page.
     *
     * @param string $productId The id of the product.
     * @param int    $amount    The amount of the product.
     *
     * @return BasketPage
     */
    public function addProductToBasketAndOpenBasket(string $productId, int $amount): BasketPage
    {
        $I = $this->user;

        //add Product to basket
        $params['cl'] = 'basket';
        $params['fnc'] = 'tobasket';
        $params['aid'] = $productId;
        $params['am'] = $amount;
        $params['anid'] = $productId;

        $this->openPage($I, $params);
        $basketPage = new BasketPage($I);
        $I->see(Translator::translate('CART'));
        return $basketPage;
    }

    /**
     * Add product to the basket and open UserCheckout page.
     *
     * @param string $productId The id of the product.
     * @param int    $amount    The amount of the product.
     *
     * @return UserCheckout
     */
    public function addProductToBasketAndOpenUserCheckout(string $productId, int $amount): UserCheckout
    {
        $I = $this->user;

        //add Product to basket
        $params['cl'] = 'user';
        $params['fnc'] = 'tobasket';
        $params['aid'] = $productId;
        $params['am'] = $amount;
        $params['anid'] = $productId;

        $this->openPage($I, $params);
        $userCheckoutPage = new UserCheckout($I);
        $breadCrumbName = Translator::translate('ADDRESS');
        $userCheckoutPage->seeOnBreadCrumb($breadCrumbName);
        return $userCheckoutPage;
    }

    /**
     * This method requires existing of name='stoken' element to present
     * in Currently loaded page.
     *
     * @param Actor $I      Actor
     * @param array $params The array with url parameters.
     */
    private function openPage(Actor $I, array $params): void
    {
        if ($I->seePageHasElement('input[name=stoken]')) {
            $params['stoken'] = $I->grabValueFrom('input[name=stoken]');
        }

        if ($I->seePageHasElement('input[name=force_sid]')) {
            $params['force_sid'] = $I->grabValueFrom('input[name=force_sid]');
        }

        $I->amOnPage('/index.php?' . http_build_query($params));
    }
}
