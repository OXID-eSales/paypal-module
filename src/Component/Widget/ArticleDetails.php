<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component\Widget;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

/**
 * Class ArticleDetails
 *
 * @package OxidSolutionCatalysts\PayPal\Component\Widget
 *
 * @mixin \OxidEsales\Eshop\Application\Component\Widget\ArticleDetails
 */
class ArticleDetails extends ArticleDetails_parent
{
    /**
     * show PayPalExpress on DetailsPage
     *
     * @var bool
     */
    protected $showPayPalExpressOnDetailsPage = null;

    public function showPayPalExpressOnDetailsPage(): bool
    {
        if (is_null($this->showPayPalExpressOnDetailsPage)) {
            $this->showPayPalExpressOnDetailsPage = false;
            $payPalConfig = oxNew(Config::class);
            $ppActive = $payPalConfig->isActive();
            $configShowPayPalProductDetailsButton = $payPalConfig->showPayPalProductDetailsButton();
            $ppExpressSessionActive = PayPalSession::isPayPalExpressOrderActive();
            $basket = Registry::getSession()->getBasket();
            // Honor the user-aware view mode (e.g. B2B overrides) and fall back to the shop default
            $showNetPrice = $basket
                ? $basket->isPriceViewModeNetto()
                : (bool) Registry::getConfig()->getConfigParam('blShowNetPrice');
            $productPrice = $this->getProduct()->getPrice();
            $isProductPriceGreaterZero = $productPrice &&
                (
                    ($showNetPrice && $productPrice->getNettoPrice() > 0) ||
                    $productPrice->getBruttoPrice() > 0
                );
            $isBasketTotalSumGreaterZero = $basket &&
                (
                    ($showNetPrice && $basket->getNettoSum() > 0) ||
                    $basket->getBruttoSum() > 0
                );

            if (
                $ppActive &&
                $configShowPayPalProductDetailsButton &&
                (
                    $isBasketTotalSumGreaterZero ||
                    $isProductPriceGreaterZero
                ) &&
                !$ppExpressSessionActive
            ) {
                $this->showPayPalExpressOnDetailsPage = true;
            }
        }

        return $this->showPayPalExpressOnDetailsPage;
    }
}
