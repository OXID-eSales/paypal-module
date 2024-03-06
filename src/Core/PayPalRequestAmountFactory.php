<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\src\Core;

use Exception;
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
    /**
     * Calculates the basket total amount and returns AmountWithBreakdown object.
     * AmountWithBreakdown is also validated for consistency. Exception will be thrown if validation fails to
     * prevent the payment to go to PayPal.
     *
     *
     * @param Basket $basket
     * @return AmountWithBreakdown
     * @throws Exception
     */
    public function getAmount(Basket $basket): AmountWithBreakdown
    {
        $netMode = Registry::getConfig()->getConfigParam('blShowNetPrice');
        $currency = $basket->getBasketCurrency();

        //Discount
        $discount = $basket->getPayPalCheckoutDiscount() + 1;
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

        if ($netMode){
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

        $breakdown->item_total = PriceToMoney::convert($itemTotal + $itemTotalAdditionalCosts , $currency);
        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        return $this->validateAmount($amount);
    }

    /**
     * Checking the AmountWithBreakdown object for calculation errors.
     *
     *
     * @throws Exception
     */
    private function validateAmount(AmountWithBreakdown $amount) :?AmountWithBreakdown
    {
        //taxes are not included in this check
        //basket amount is brut
        //we are forcing 0% tax in amount breakdown to avoid rounding issues in VAT calculations.
        $breakdownValue = (float)$amount->breakdown->item_total->value - (float)$amount->breakdown->discount->value;

        if($amount->value == $breakdownValue){
            return $amount;
        }

        throw new Exception('Amount is not valid. Payment request not sent.');
    }

}
