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
        $amount->value = (float)number_format($this->brutBasketTotal, 2, '.', '');
        $amount->currency_code = $this->getCurrency()->name;
        $amount->breakdown = $this->calculateBreakdown();

        return $amount;
    }

    /**
     * Calculates the breakdown components of the amount
     */
    protected function calculateBreakdown(): AmountBreakdown
    {
        $breakdown = new AmountBreakdown();

        // Process discount
        $this->processDiscount($breakdown);

        // Calculate item total
        $breakDownItemTotal = $this->calculateBreakdownItemTotal();
        $breakdown->item_total = PriceToMoney::convert($breakDownItemTotal, $this->getCurrency());

        // Add tax total
        $breakdown->tax_total = PriceToMoney::convert(0, $this->getCurrency());

        // Process shipping
        $this->processShipping($breakdown);

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
        $itemTotal = $this->itemTotal;
        $discount = $this->discount;
        $itemTotalAdditionalCosts = $this->itemTotalAdditionalCosts;

        return $this->netMode ? $itemTotal : $itemTotal /*- $discount*/ + $itemTotalAdditionalCosts;
    }

    /**
     * Processes shipping for the breakdown
     */
    protected function processShipping(AmountBreakdown $breakdown): void
    {
        // Add shipping when available
        if ($this->shipping) {
            $breakdown->shipping = PriceToMoney::convert($this->shipping, $this->getCurrency());
        }

        $shouldCombineShippingWithItems =
            (!$this->netMode && $this->enteredNetPrice) ||
            ($this->netMode && $this->isPrecisionAboveLimit());

        // For prices entered in net and precision limit above 2
        // the shipping should be combined with basket total because of the rounding errors
        if ($shouldCombineShippingWithItems) {
            $breakdown->shipping = null;
            $combinedTotal = $this->itemTotal + $this->shipping;
            $breakdown->item_total = PriceToMoney::convert($combinedTotal, $this->getCurrency());
        }
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
