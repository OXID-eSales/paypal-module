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
     * Checks if any product in basket is virtual and does not require real delivery.
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
     * Checks if all products in basket are virtual and do not require delivery.
     * Returns TRUE if each product is virtual, FALSE if at least one phyical item is contained.
     *
     * @return bool
     */
    public function isEntirelyVirtualPayPalBasket()
    {
        $countVirtualProducts = $countTotalProducts = 0;

        $products = $this->getBasketArticles();
        foreach ($products as $product) {
            $countTotalProducts++;
            if ($product->isVirtualPayPalArticle()) {
                $countVirtualProducts++;
            }
        }

        return ($countTotalProducts === $countVirtualProducts);
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
     * and returns sum of collected discounts value.
     */
    public function getPayPalCheckoutDiscount(): float
    {
        $config = Registry::getConfig();
        $netMode = $this->isCalculationModeNetto();
        $defaultVAT = $config->getConfigParam('dDefaultVAT');
        $discount = 0.0;

        $totalDiscount = $this->getTotalDiscount();

        if ($totalDiscount) {
            $discount += $netMode ? $totalDiscount->getNettoPrice() : $totalDiscount->getBruttoPrice();
        }

        //vouchers
        $vouchers = $this->getVouchers();
        foreach ($vouchers as $voucher) {
            $voucherPrice = oxNew(Price::class);
            $voucherPrice->setNettoMode($netMode);
            $voucherPrice->setPrice($voucher->dVoucherdiscount, (float)$defaultVAT);

            $discount += $netMode ? $voucherPrice->getNettoPrice() : $voucherPrice->getBruttoPrice();
        }

        return $discount;
    }

    /**
     * Collect the brut-sum of all articles in Basket.
     */
    public function getPayPalCheckoutItems(bool $isOxidSum = true): float
    {
        $result = 0;
        $netMode = Registry::getConfig()->getConfigParam('blShowNetPrice');

        if ($isOxidSum) {
            $result += $netMode ? $this->getProductsPrice()->getSum(false) : $this->getBruttoSum();
        } else {
            foreach ($this->getContents() as $basketItem) {
                $itemUnitPrice = $basketItem->getUnitPrice();
                $result += $itemUnitPrice->getBruttoPrice() * $basketItem->getAmount();
            }
        }

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

    /**
     * add a ShippingPrice for PPExpress if it is not defined before to prevent overcharge.
     * @param float $defaultShippingPriceExpress
     */
    public function addShippingPriceForExpress(float $defaultShippingPriceExpress): void
    {
        $oPrice = oxNew(Price::class);
        $oPrice->setPrice($defaultShippingPriceExpress);
        $this->setDeliveryPrice($oPrice);
        $this->calculateBasket(true);
    }

    /**
     * Return sum of:
     *  - Wrapping-Costs
     *  - Gift-Cards
     *  - Payment-Costs
     *  - Delivery-Costs
     */
    public function getAdditionalPayPalCheckoutItemCosts(): float
    {
        $result = 0;
        $result += $this->getPayPalCheckoutWrapping();
        $result += $this->getPayPalCheckoutGiftCard();
        $result += $this->getPayPalCheckoutPayment();
        $result += $this->getPayPalCheckoutDeliveryCosts();

        return $result;
    }

    private function getFloatFromPrice(Price $price): float
    {
        return $this->isCalculationModeNetto() ? $price->getNettoPrice(): $price->getBruttoPrice();
    }
}
