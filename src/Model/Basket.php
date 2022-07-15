<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;

/**
 * PayPal basket class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Basket
 */
class Basket extends Basket_parent
{
    /**
     * Checks if products in basket ar virtual and does not require real delivery.
     * Returns TRUE if virtual
     *
     * @return bool
     */
    public function isVirtualPayPalBasket()
    {
        $isVirtual = true;

        $products = $this->getBasketArticles();
        foreach ($products as $product) {
            if (!$product->isVirtualPayPalArticle()) {
                $isVirtual = false;
                break;
            }
        }

        return $isVirtual;
    }

    /**
     * Checks if fraction quantity items (with 1.3 amount) exists in basket.
     *
     * @return bool
     */
    public function isFractionQuantityItemsPresent()
    {
        $fractionItemsPresent = false;

        foreach ($this->getContents() as $basketItem) {
            $amount = $basketItem->getAmount();
            if ((int) $amount != $amount) {
                $fractionItemsPresent = true;
                break;
            }
        }

        return $fractionItemsPresent;
    }

    /**
     * Returns wrapping Brutto cost
     *
     * @return double
     */
    public function getPayPalCheckoutWrapping()
    {
        $amount = 0.0;

        $wrappingCost = $this->getCosts('oxwrapping');
        if ($wrappingCost) {
            $amount = $wrappingCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Returns greeting card Brutto Costs
     *
     * @return double
     */
    public function getPayPalCheckoutGiftCard()
    {
        $amount = 0.0;

        $giftCardCost = $this->getCosts('oxgiftcard');
        if ($giftCardCost) {
            $amount = $giftCardCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Returns payment costs brutto value.
     *
     * @return double
     */
    public function getPayPalCheckoutPayment()
    {
        $amount = 0.0;

        $paymentCost = $this->getCosts('oxpayment');
        if ($paymentCost) {
            $amount = $paymentCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Returns delivery costs in Brutto!
     *
     * @return double
     */
    public function getPayPalCheckoutDeliveryCosts()
    {
        $amount = 0.0;

        $deliveryCost = $this->getCosts('oxdelivery');
        if ($deliveryCost) {
            $amount = $deliveryCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Collects all basket discounts (basket, payment and vouchers)
     * and returns sum of collected discounts.
     *
     * @return float
     */
    public function getPayPalCheckoutDiscount(): float
    {
        // collect discounts
        $discount = 0.0;

        $totalDiscount = $this->getTotalDiscount();

        if ($totalDiscount) {
            $discount += $totalDiscount->getBruttoPrice();
        }

        //if payment costs are negative, adding them to discount
        if (($costs = $this->getPaymentCosts()) < 0) {
            $discount += ($costs * -1);
        }

        // vouchers..
        $vouchers = (array) $this->getVouchers();
        foreach ($vouchers as $voucher) {
            $discount += round($voucher->dVoucherdiscount, 2);
        }

        return $discount;
    }

    /**
     * Returns array of basket oxarticle objects
     *
     * @return array
     */
    public function getBasketArticles()
    {
        /* This is a dirty hack. The getBasketArticles method is called when order emails are rendered
         * In this method there is a call for the product: $oProduct = $oBasketItem->getArticle(true);
         * The problem is that this method call checks the inventory. If we have bought a "last" article
         * and the article is then sold out, then the stock check leads to an error, because logically
         * there is no article left. This is not noticeable with normal payments, since the e-mail is
         * sent at a time when the order has not yet been finally saved and the stock has not yet been
         * adjusted. With PayPal payments, the order email will be sent later, when the order is saved
         * completely and the stock has changed (in our Example to 0)
         */
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2];
        if ($caller['function'] === 'fetch' && $caller['class'] === 'Smarty') {
            return $this->getBasketArticlesWithoutProductCheck();
        }
        return parent::getBasketArticles();
    }

    /**
     * This is a copy of the original getBasketArticles with the only difference that
     * getArticle method does not check the article
     *
     * Returns array of basket oxarticle objects
     *
     * @return array
     */
    public function getBasketArticlesWithoutProductCheck()
    {
        $callerFunction = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $aBasketArticles = [];
        /** @var \oxBasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $sItemKey => $oBasketItem) {
            try {
                $oProduct = $oBasketItem->getArticle();

                if ($this->getConfig()->getConfigParam('bl_perfLoadSelectLists')) {
                    // marking chosen select list
                    $aSelList = $oBasketItem->getSelList();
                    if (is_array($aSelList) && ($aSelectlist = $oProduct->getSelectLists($sItemKey))) {
                        reset($aSelList);
                        foreach ($aSelList as $conkey => $iSel) {
                            $aSelectlist[$conkey][$iSel]->selected = 1;
                        }
                        $oProduct->setSelectlist($aSelectlist);
                    }
                }
            } catch (\OxidEsales\Eshop\Core\Exception\NoArticleException $oEx) {
                \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx);
                $this->removeItem($sItemKey);
                $this->calculateBasket(true);
                continue;
            } catch (\OxidEsales\Eshop\Core\Exception\ArticleInputException $oEx) {
                \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx);
                $this->removeItem($sItemKey);
                $this->calculateBasket(true);
                continue;
            }

            $aBasketArticles[$sItemKey] = $oProduct;
        }

        return $aBasketArticles;
    }

    /**
     * collect the netto-sum of all articles in Basket
     * and returns sum of all costs.
     *
     * Normally we could use the method: $this->getProductsPrice()->getSum(true)
     * to calculate the total net amount. However, since Paypal calculates the
     * sum of the items on the basis of the rounded net prices, rounding errors
     * can occur in the total. Therefore we calculate the sum over the following
     * iteration.
     *
     * @return double
     */
    public function getPayPalCheckoutItems($isOxidSum = true)
    {
        $result = 0;
        if ($isOxidSum) {
            $result += $this->getBruttoSum();
        } else {
            foreach ($this->getContents() as $basketItem) {
                $itemUnitPrice = $basketItem->getUnitPrice();
                $result += $itemUnitPrice->getBruttoPrice() * $basketItem->getAmount();
            }
        }

        // Wrapping-Costs, Gift-Cards and Payment-Costs are also Items for PayPal, so we add them
        $result += $this->getPayPalCheckoutWrapping();
        $result += $this->getPayPalCheckoutGiftCard();
        $result += $this->getPayPalCheckoutPayment();
        $result += $this->getPayPalCheckoutDeliveryCosts();

        return $result;
    }

    /**
     * difference between OXID Vat rounding and PayPal Vat Rounding
     *
     * @return double
     */
    public function getPayPalCheckoutRoundDiff()
    {
        $result = 0;
        if ($this->isCalculationModeNetto()) {
            $result += $this->getPayPalCheckoutItems(true) - $this->getPayPalCheckoutItems(false);
        }
        return $result;
    }

    /**
     * Check if variants of the given product are already in the basket
     * @param \OxidEsales\Eshop\Application\Model\Article $product
     * @return bool
     */
    public function hasProductVariantInBasket(\OxidEsales\Eshop\Application\Model\Article $product)
    {
        $return = false;

        $variantIds = $product->getVariantIds();
        foreach ($variantIds as $id) {
            if ($this->getArtStockInBasket($id)) {
                $return = true;
                break;
            }
        }

        return $return;
    }
}
