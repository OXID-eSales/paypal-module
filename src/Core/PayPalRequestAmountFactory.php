<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Model\Article;
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
    private bool $enteredNetPrice = false;
    private bool $netMode = false;
    private stdClass $currency;
    private $discount;
    private $itemTotal;
    private $itemTotalAdditionalCosts;
    private $brutBasketTotal;
    private $shipping;

    public function __construct()
    {
        $this->config = Registry::getConfig();
        $this->enteredNetPrice = $this->getConfig()->getConfigParam('blEnterNetPrice');
    }

    public function getAmount(Basket $basket): AmountWithBreakdown
    {
        $this->basket = $basket;
        $this->collectPriceData();

        return $this->createAmountWithBreakdown();
    }

    /**
     * Collects all price data needed for calculation
     */
    protected function collectPriceData(): void
    {
        $this->setCurrency($this->basket->getBasketCurrency());
        $this->netMode = $this->basket->isCalculationModeNetto();
        $this->discount = $this->basket->getPayPalCheckoutDiscount();
        $this->itemTotal = $this->basket->getPayPalCheckoutItems();
        $this->itemTotalAdditionalCosts = $this->basket->getAdditionalPayPalCheckoutItemCosts();
        $this->brutBasketTotal = $this->basket->getPrice()->getBruttoPrice();
        $this->shipping = $this->basket->getPayPalCheckoutDeliveryCosts();
    }

    /**
     * Checks if the currency precision is above PayPal's limit
     */
    protected function isPrecisionAboveLimit(): bool
    {
        return $this->currency->decimal > 2;
    }

    /**
     * Creates the base amount object with total value
     */
    protected function createAmountWithBreakdown(): AmountWithBreakdown
    {
        $amount = new AmountWithBreakdown();
        //https://developer.paypal.com/docs/api/orders/v2/
        //the amount = item_total + tax_total + shipping + handling + insurance - shipping_discount - discount.
        $amount->value = (float)number_format($this->brutBasketTotal, 2, '.', '');
        $amount->currency_code = $this->getCurrency()->name;
        //Breakdown provides details such as:
        //total item amount, total tax amount, shipping, handling, insurance, and discounts, if any.
        $amount->breakdown = $this->calculateBreakdown($amount->value);

        $this->roundingIssueSolutionHandling($amount);

        return $amount;
    }

    /**
     * Calculates the breakdown components of the amount
     */
    protected function calculateBreakdown(float $amount): AmountBreakdown
    {
        $breakdown = new AmountBreakdown();

        $breakdown->shipping =
            PriceToMoney::convert($this->basket->getPayPalCheckoutDeliveryCosts(), $this->getCurrency());
        $breakdown->discount =
            PriceToMoney::convert($this->basket->getPayPalCheckoutDiscount(), $this->getCurrency());

        $breakdown->tax_total =
            PriceToMoney::convert(0, $this->getCurrency());

        $breakdown->item_total = PriceToMoney::convert(
            $amount +
            (float)$breakdown->discount->value -
            (float)$breakdown->shipping->value, $this->getCurrency());

        return $breakdown;
    }

    /**
     * Processes discount for the breakdown
     */
    protected function processDiscount(AmountBreakdown $breakdown): void
    {
        $discount = (!$this->netMode && $this->discount < 0) ? 0 : $this->discount;
        $brutDiscountValue = $this->itemTotal + $this->itemTotalAdditionalCosts - $this->brutBasketTotal;

        // Possible price surcharge
        if ($this->netMode && $brutDiscountValue < 0) {
            $brutDiscountValue = 0;
        }

        if ($discount) {
            $breakdown->discount = PriceToMoney::convert(
                $this->netMode ? $brutDiscountValue : $discount,
                $this->getCurrency()
            );
        }
    }

    /**
     * Calculates the item total for the breakdown
     */
    protected function calculateBreakdownItemTotal(): float
    {
        return $this->basket->getPayPalCheckoutItems();
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

    /**
     * @param \OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown $amount
     * @return void
     */
    public function roundingIssueSolutionHandling(AmountWithBreakdown $amount): void
    {
        $amountBreakdownValueCheck =
            (float)number_format(
                (float)$amount->breakdown->item_total->value
                    - $amount->breakdown->discount->value
                    + $this->shipping,
                2,
                '.',
                ''
            );

        $amountDiff = (float)number_format(
            $amountBreakdownValueCheck - $amount->value,
            2,
            '.',
            ''
        );

        if ($amount->value != $amountBreakdownValueCheck) {
            if ($this->isPrecisionAboveLimit()) {
                if ($amountBreakdownValueCheck > $amount->value) {
                    $amount->breakdown->initShippingDiscount();
                    $amount->breakdown->shipping_discount->value = $amountDiff;
                    $amount->breakdown->shipping_discount->currency_code = $amount->currency_code;
                }
                if ($amountBreakdownValueCheck < $amount->value) {
                    $amount->breakdown->initHandling();
                    $amount->breakdown->handling->value = $amountDiff;
                    $amount->breakdown->handling->currency_code = $amount->currency_code;
                }
            }
        }
    }
}
