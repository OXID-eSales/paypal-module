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
     * Returns wrapping cost as Price object
     *
     * @return null|Price
     */
    public function getPayPalCheckoutWrapping()
    {
        $wrappingCost = $this->getCosts('oxwrapping');
        return $wrappingCost instanceof Price ? $wrappingCost : null;
    }

    /**
     * Returns greeting card cost as Price object
     *
     * @return null|Price
     */
    public function getPayPalCheckoutGiftCard()
    {
        $giftCardCost = $this->getCosts('oxgiftcard');
        return $giftCardCost instanceof Price ? $giftCardCost : null;
    }

    /**
     * Returns payment costs as Price object
     *
     * @return null|Price
 */
    public function getPayPalCheckoutPayment()
    {
        $paymentCost = $this->getCosts('oxpayment');
        return $paymentCost instanceof Price ? $paymentCost : null;
    }

    /**
     * Returns delivery costs as Price object
     *
     * @return null|Price
 */
    public function getPayPalCheckoutDeliveryCosts()
    {
        $deliveryCost = $this->getCosts('oxdelivery');
        return $deliveryCost instanceof Price ? $deliveryCost : null;
    }

    /**
     * Collects all basket discounts (basket, payment and vouchers)
     * and returns sum of collected discounts value.
     */
    public function getPayPalCheckoutDiscount(bool $netMode): float
    {
        $config = Registry::getConfig();
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

    public function getPayPalCheckoutDiscountVat(bool $netMode): float
    {
        $config = Registry::getConfig();
        $defaultVAT = $config->getConfigParam('dDefaultVAT');
        $discount = 0.0;

        $totalDiscount = $this->getTotalDiscount();

        if ($totalDiscount) {
            $discount += $totalDiscount->getVatValue();
        }

        //vouchers
        $vouchers = $this->getVouchers();
        foreach ($vouchers as $voucher) {
            $voucherPrice = oxNew(Price::class);
            $voucherPrice->setNettoMode($netMode);
            $voucherPrice->setPrice($voucher->dVoucherdiscount, (float)$defaultVAT);

            $discount += $totalDiscount->getVatValue();
        }

        return $discount;
    }

    /**
     * Collect the brut-sum of all articles in Basket.
     */
    public function getPayPalCheckoutItems(bool $isOxidSum = true): float
    {
        $result = 0;

        if ($isOxidSum) {
            $result += $this->isCalculationModeNetto() ? $this->getProductsPrice()->getSum(false) : $this->getBruttoSum();
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
            $result += $this->getPayPalCheckoutItems() - $this->getPayPalCheckoutItems(false);
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
        $wrappingCost = $this->getPayPalCheckoutWrapping();
        $giftCardCost = $this->getPayPalCheckoutGiftCard();
        $paymentCost = $this->getPayPalCheckoutPayment();
        $deliveryCost = $this->getPayPalCheckoutDeliveryCosts();
        $result += $wrappingCost ? $this->getFloatFromPrice($wrappingCost) : 0;
        $result += $giftCardCost ? $this->getFloatFromPrice($giftCardCost) : 0;
        $result += $paymentCost ? $this->getFloatFromPrice($paymentCost) : 0;
        $result += $deliveryCost ? $this->getFloatFromPrice($deliveryCost) : 0;

        return $result;
    }

    private function getFloatFromPrice(Price $price): float
    {
        return $this->isCalculationModeNetto() ? $price->getNettoPrice(): $price->getBruttoPrice();
    }
}
