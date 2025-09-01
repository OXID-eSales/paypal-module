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
use stdClass;

/**
 * Class PayPalRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PayPalRequestAmountFactory
{
    /**
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    private Basket $basket;
    /**
     * @var \OxidEsales\Eshop\Core\Config
     */
    private $config;
    private stdClass $currency;

    public function __construct()
    {
        $this->config = Registry::getConfig();
    }

    public function getAmount(Basket $basket): AmountWithBreakdown
    {
        $this->basket = $basket;
        $this->setCurrency($this->basket->getBasketCurrency());

        return $this->createAmountWithBreakdown();
    }

    /**
     * Creates the base amount object with total value
     */
    protected function createAmountWithBreakdown(): AmountWithBreakdown
    {
        $amount = new AmountWithBreakdown();
        //https://developer.paypal.com/docs/api/orders/v2/
        //the amount = item_total + tax_total + shipping + handling + insurance - shipping_discount - discount.
        $amount->value = (float)number_format(
            $this->basket->getPrice()->getBruttoPrice(),
            2,
            '.',
            ''
        );
        $amount->currency_code = $this->getCurrency()->name;
        //Breakdown provides details such as:
        //total item amount, total tax amount, shipping, handling, insurance, and discounts, if any.
        $amount->breakdown = $this->calculateBreakdown($amount->value);

        return $amount;
    }

    /**
     * Calculates the breakdown components of the amount
     */
    protected function calculateBreakdown(float $amount): AmountBreakdown
    {
        $breakdown = new AmountBreakdown();

        $currency = $this->getCurrency();

        // Shipping costs (rounded to currency precision)
        $breakdown->shipping = PriceToMoney::convert($this->basket->getPayPalCheckoutDeliveryCosts(), $currency);

        // Discount: use basket API value; in gross mode, negative discount should not increase total
        $discount = (float)$this->basket->getPayPalCheckoutDiscount();
        if (!$this->basket->isCalculationModeNetto() && $discount < 0) {
            $discount = 0.0;
        }
        $breakdown->discount = PriceToMoney::convert($discount, $currency);

        // Tax total is zero for gross mode in this factory; NET handling is done elsewhere if needed
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        /*
         * The subtotal for all items.
         * Required if the request includes purchase_units[].items[].unit_amount.
         * Must equal the sum of (items[].unit_amount * items[].quantity) for all items.
         * item_total.value can not be a negative number.
         */
        $itemTotal = (float)$this->basket->getPayPalCheckoutItems();
        $breakdown->item_total = PriceToMoney::convert($itemTotal, $currency);

        return $breakdown;
    }

    /**
     * @return object|\OxidEsales\Eshop\Core\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getCurrency(): stdClass
    {
        $currency = clone $this->currency;
        $currency->decimal = 2;

        return $currency;
    }

    public function setCurrency(stdClass $currency): void
    {
        $this->currency = $currency;
    }
}
