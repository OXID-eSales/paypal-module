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
     * Returns wrapping cost value
     *
     * @return double
     */
    public function getPayPalWrappingCosts()
    {
        $amount = 0.0;

        $wrappingCost = $this->getCosts('oxwrapping');
        if ($wrappingCost) {
            $amount = $this->isCalculationModeNetto() ?
                $wrappingCost->getNettoPrice() :
                $wrappingCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Returns greeting card cost value
     *
     * @return double
     */
    public function getPayPalGiftCardCosts()
    {
        $amount = 0.0;

        $giftCardCost = $this->getCosts('oxgiftcard');
        if ($giftCardCost) {
            $amount = $this->isCalculationModeNetto() ?
                $giftCardCost->getNettoPrice() : $giftCardCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Returns payment costs netto or brutto value.
     *
     * @return double
     */
    public function getPayPalPaymentCosts()
    {
        $amount = 0.0;

        $paymentCost = $this->getCosts('oxpayment');
        if ($paymentCost) {
            $amount = $this->isCalculationModeNetto() ?
                $paymentCost->getNettoPrice() : $paymentCost->getBruttoPrice();
        }

        return $amount;
    }

    /**
     * Collects all basket discounts (basket, payment and vouchers)
     * and returns sum of collected discounts.
     *
     * @return double
     */
    public function getDiscountSumPayPalBasket()
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
     * Calculates basket costs (payment, GiftCard and gift card)
     * and returns sum of all costs.
     *
     * @return double
     */
    public function getSumOfCostOfAllItemsPayPalBasket()
    {
        // basket items sum
        $allCosts = $this->getProductsPrice()->getSum($this->isCalculationModeNetto());

        //adding to additional costs only if payment is > 0
        if (($costs = $this->getPayPalPaymentCosts()) > 0) {
            $allCosts += $costs;
        }

        // wrapping costs
        $allCosts += $this->getPayPalWrappingCosts();

        // greeting card costs
        $allCosts += $this->getPayPalGiftCardCosts();

        return $allCosts;
    }

    /**
     * Returns absolute VAT value.
     *
     * @return float
     */
    public function getPayPalBasketVatValue()
    {
        $basketVatValue = 0;
        $basketVatValue += $this->getPayPalProductVat();
        $basketVatValue += $this->getPayPalWrappingVat();
        $basketVatValue += $this->getPayPalGiftCardVat();
        $basketVatValue += $this->getPayPalPayCostVat();

        if ($this->getDeliveryCosts() < round($this->getDeliveryCosts(), 2)) {
            return floor($basketVatValue * 100) / 100;
        }

        return $basketVatValue;
    }

    /**
     * Return products VAT.
     *
     * @return double
     */
    public function getPayPalProductVat()
    {
        $productVatList = $this->getProductVats(false);
        $productVatSum = array_sum($productVatList);

        return $productVatSum;
    }

    /**
     * Return wrapping VAT.
     *
     * @return double
     */
    public function getPayPalWrappingVat()
    {
        $wrappingVat = 0.0;

        $wrapping = $this->getCosts('oxwrapping');
        if ($wrapping && $wrapping->getVatValue()) {
            $wrappingVat = $wrapping->getVatValue();
        }

        return $wrappingVat;
    }

    /**
     * Return gift card VAT.
     *
     * @return double
     */
    public function getPayPalGiftCardVat()
    {
        $giftCardVat = 0.0;

        $giftCard = $this->getCosts('oxgiftcard');
        if ($giftCard && $giftCard->getVatValue()) {
            $giftCardVat = $giftCard->getVatValue();
        }

        return $giftCardVat;
    }

    /**
     * Return payment VAT.
     *
     * @return double
     */
    public function getPayPalPayCostVat()
    {
        $paymentVAT = 0.0;

        $paymentCost = $this->getCosts('oxpayment');
        if ($paymentCost && $paymentCost->getVatValue()) {
            $paymentVAT = $paymentCost->getVatValue();
        }

        return $paymentVAT;
    }

    /**
     * @param $sProductID
     * @param $dAmount
     * @param null $aSel
     * @param null $aPersParam
     * @param false $blOverride
     * @param false $blBundle
     * @param null $sOldBasketItemId
     * @return mixed
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function addToBasket(
        $sProductID,
        $dAmount,
        $aSel = null,
        $aPersParam = null,
        $blOverride = false,
        $blBundle = false,
        $sOldBasketItemId = null
    ) {

        $result = parent::addToBasket(
            $sProductID,
            $dAmount,
            $aSel,
            $aPersParam,
            $blOverride,
            $blBundle,
            $sOldBasketItemId
        );
        // At the moment we don't need this overload-method.
        // I'll leave them here as a placeholder for now
        $session = Registry::getSession();

        if ($subscriptionPlanId = $session->getVariable('subscriptionPlanIdForBasket')) {
            $session->deleteVariable('subscriptionPlanIdForBasket');
        }

        return $result;
    }

    /**
     * Executes all needed functions to calculate basket price and other needed
     * info
     *
     * @param bool $blForceUpdate set this parameter to TRUE to force basket recalculation
     *
     * @return null
     */
    public function calculateBasket($blForceUpdate = false)
    {
        $isSubscriptionBasket = false;
        foreach ($this->_aBasketContents as $oBasketItem) {
            $basketArticle = $oBasketItem->getArticle(true);
            $basketArticle->getId();
            $article = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
            $article->load($basketArticle->getId());
            if ($article->isPayPalProductLinked()) {
                $isSubscriptionBasket = true;
                break;
            }
        }

        if (!$isSubscriptionBasket || !($this->_blUpdateNeeded || $blForceUpdate)) {
            return parent::calculateBasket($blForceUpdate);
        }

        $this->_aCosts = [];

        //  1. saving basket to the database
        $this->_save();

        //  2. remove all bundles
        $this->_clearBundles();

        //  3. generate bundle items
        $this->_addBundles();

        //  4. calculating item prices
        $this->_calcItemsPrice();

        //  5. calculating/applying discounts
        $this->_calcBasketDiscount();

        //  6. calculating basket total discount
        $this->_calcBasketTotalDiscount();

        //  7. check for vouchers
        $this->_calcVoucherDiscount();

        //  8. applies all discounts to pricelist
        $this->_applyDiscounts();

        $zeroPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
        $zeroPrice->setPrice(0.0);

        //  9. calculating additional costs:
        //  9.1: delivery
        $this->setCost('oxdelivery', $zeroPrice);

        //  9.2: adding wrapping and gift card costs
        $this->setCost('oxwrapping', $zeroPrice);

        $this->setCost('oxgiftcard', $zeroPrice);

        //  9.3: adding payment cost
        $this->setCost('oxpayment', $zeroPrice);

        //  10. calculate total price
        $this->_calcTotalPrice();

        //  11. formatting discounts
        $this->formatDiscount();

        //  12.setting to up-to-date status
        $this->afterUpdate();
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
