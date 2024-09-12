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
        $netMode = $basket->isCalculationModeNetto();
        $currency = $basket->getBasketCurrency();

        //Discount
        $discount = $basket->getPayPalCheckoutDiscount();
        //Item total cost
        $itemTotal = $basket->getPayPalCheckoutItems();

        $itemTotalAdditionalCosts = $basket->getAdditionalPayPalCheckoutItemCosts();

        $brutBasketTotal = $basket->getPrice()->getBruttoPrice();
        $brutDiscountValue = $itemTotal + $itemTotalAdditionalCosts - $brutBasketTotal;

        // possible price surcharge
        if ($netMode && $brutDiscountValue < 0) {
            $brutDiscountValue = 0;
        }

        if (!$netMode && $discount < 0) {
            $itemTotal -= $discount;
            $discount = 0;
        }

        if ($netMode) {
            $total = $brutBasketTotal;
        } else {
            $total = $itemTotal - $discount + $itemTotalAdditionalCosts;
        }

        $total = PriceToMoney::convert($total, $currency);

        //Total amount
        $amount = new AmountWithBreakdown();
        $amount->value = $total->value;
        $amount->currency_code = $total->currency_code;

        //Cost breakdown
        $breakdown = $amount->breakdown = new AmountBreakdown();

        if ($discount) {
            $breakdown->discount = PriceToMoney::convert($netMode ? $brutDiscountValue : $discount, $currency);
        }

        $breakdown->item_total = PriceToMoney::convert($total->value, $currency);
        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        return $amount;
    }
}
