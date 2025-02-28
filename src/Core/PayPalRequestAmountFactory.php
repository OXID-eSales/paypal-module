<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;

/**
 * Class PayPalRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PayPalRequestAmountFactory
{

    public function getAmount(Basket $basket): AmountWithBreakdown
    {
        $enteredNetPrice = Registry::getConfig()->getConfigParam('blEnterNetPrice');
        $netMode = $basket->isCalculationModeNetto();
        $currency = $basket->getBasketCurrency();
        //only two decimal place precision is supported in PayPal
        $isPrecisionAboveLimit = $currency->decimal > 2;
        //hardcode precision limit for other processes that uses currency object
        $currency->decimal = 2;

        //Discount
        $discount = $basket->getPayPalCheckoutDiscount();
        //Item total cost
        $itemTotal = $basket->getPayPalCheckoutItems();

        $itemTotalAdditionalCosts = $basket->getAdditionalPayPalCheckoutItemCosts();

        $brutBasketTotal = $basket->getPrice()->getBruttoPrice();
        $brutDiscountValue = $itemTotal + $itemTotalAdditionalCosts - $brutBasketTotal;
        $shipping = $basket->getPayPalCheckoutDeliveryCosts();

        // possible price surcharge
        if ($netMode && $brutDiscountValue < 0) {
            $brutDiscountValue = 0;
        }

        if (!$netMode && $discount < 0) {
            $itemTotal -= $discount;
            $discount = 0;
        }

        $total = $netMode ? $itemTotal : ($itemTotal - $discount + $itemTotalAdditionalCosts);
        $total = PriceToMoney::convert($total, $currency);

        //Total amount
        $amount = new AmountWithBreakdown();
        $amount->value = (float)number_format($brutBasketTotal, 2, '.', '');
        $amount->currency_code = $total->currency_code;

        //Cost breakdown
        $breakdown = $amount->breakdown = new AmountBreakdown();

        if ($discount) {
            $breakdown->discount = PriceToMoney::convert($netMode ? $brutDiscountValue : $discount, $currency);
        }

        $breakDownItemTotal = $netMode ? $total->value : $itemTotal;
        $breakdown->item_total = PriceToMoney::convert($breakDownItemTotal, $currency);
        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        //Shipping cost
        $delivery = $basket->getPayPalCheckoutDeliveryCosts();
        if ($delivery) {
            $breakdown->shipping = PriceToMoney::convert(
                $delivery,
                $currency
            );
        }

        //For prices entered in net and precision limit above 2
        //the shipping should be combined with basket total because of the rounding errors
        if (
            ($enteredNetPrice && !$netMode)
            || ($netMode && $isPrecisionAboveLimit)
        ){
            $breakdown->shipping = null;
            $breakDownItemTotal = $itemTotal + $shipping;
            $breakdown->item_total = PriceToMoney::convert($breakDownItemTotal, $currency);
        }

        return $amount;
    }
}
