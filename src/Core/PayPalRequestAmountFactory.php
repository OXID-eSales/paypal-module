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
        $breakdown->shipping = PriceToMoney::convert($this->basket->getPayPalCheckoutDeliveryCosts(), $currency);
        $breakdown->discount = PriceToMoney::convert($this->basket->getPayPalCheckoutDiscountBrutto(), $currency);
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        /*
         * The subtotal for all items.
         * Required if the request includes purchase_units[].items[].unit_amount.
         * Must equal the sum of (items[].unit_amount * items[].quantity) for all items.
         * item_total.value can not be a negative number.
         */
        $itemTotal = 0.0;
        foreach ((array)$this->basket->getContents() as $basketItem) {
            // Each $basketItem is \OxidEsales\Eshop\Application\Model\BasketItem
            $unitPrice = $basketItem->getUnitPrice();
            if ($unitPrice) {
                $qty = (float)$basketItem->getAmount();
                $val = (float)$unitPrice->getPrice(); // net or gross depending on shop mode
                $itemTotal += $qty * $val;
            }
        }
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
