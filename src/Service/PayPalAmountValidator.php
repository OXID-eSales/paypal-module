<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Utils\AmountFormatter;

class PayPalAmountValidator
{
    private const DECIMAL_PRECISION = 2;
    private const MAX_DECIMALS = 2;
    private const ROUNDING_ADJUSTMENT = 0.01;

    /**
     * Validate and adjust PayPal order amounts to prevent rounding errors
     */
    public function validateAndAdjustOrder(array $orderData): array
    {
        // Calculate items total (including subtracting discounts if provided in breakdown)
        $items = $orderData['items'] ?? [];
        $itemsTotal = $this->calculateItemsTotal($items, $orderData['breakdown'] ?? []);

        // If no items are present, we are not in NET items mode; do not adjust amounts
        if (empty($items)) {
            return $orderData;
        }

        // Ensure item_total in breakdown matches sum of item unit amounts
        $computedItemTotal = $this->roundToPayPalPrecision($this->calculateItemTotalFromItems($items));
        $currentItemTotal = isset($orderData['breakdown']['item_total']['value'])
            ? $this->roundToPayPalPrecision((float)$orderData['breakdown']['item_total']['value'])
            : null;
        if ($currentItemTotal === null || $currentItemTotal !== $computedItemTotal) {
            $itemCurrencyCode = $orderData['breakdown']['item_total']['currency_code']
                ?? ($orderData['items'][0]['unit_amount']['currency_code']
                    ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
            $orderData['breakdown']['item_total'] = [
                'currency_code' => $itemCurrencyCode,
                'value' => $this->formatAmount($computedItemTotal)
            ];
        }

        // Ensure tax_total in breakdown matches sum of item taxes
        $computedTaxTotal = $this->roundToPayPalPrecision($this->calculateTaxTotalFromItems($items));
        $currentTaxTotal = isset($orderData['breakdown']['tax_total']['value'])
            ? $this->roundToPayPalPrecision((float)$orderData['breakdown']['tax_total']['value'])
            : null;
        if ($currentTaxTotal === null || $currentTaxTotal !== $computedTaxTotal) {
            // Determine currency code for tax_total
            $currencyCode = $orderData['breakdown']['tax_total']['currency_code']
                ?? ($orderData['items'][0]['unit_amount']['currency_code']
                    ?? ($orderData['breakdown']['item_total']['currency_code']
                        ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD')));
            $orderData['breakdown']['tax_total'] = [
                'currency_code' => $currencyCode,
                'value' => $this->formatAmount($computedTaxTotal)
            ];
        }

        $amountValue = isset($orderData['amount_value']) ? (float)$orderData['amount_value'] : null;
        $breakdownTotal = $this->calculateBreakdownTotal($orderData['breakdown'] ?? [], $amountValue);

        // Check if adjustment is needed
        $difference = round($itemsTotal - $breakdownTotal, self::DECIMAL_PRECISION);

        if (abs($difference) > 0) {
            $orderData = $this->adjustAmounts($orderData, $difference);

            $recomputedItems = $orderData['items'] ?? [];
            $recomputedItemTotal = $this->roundToPayPalPrecision($this->calculateItemTotalFromItems($recomputedItems));
            $itemCurrencyCode = $orderData['breakdown']['item_total']['currency_code']
                ?? ($recomputedItems[0]['unit_amount']['currency_code']
                    ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
            $orderData['breakdown']['item_total'] = [
                'currency_code' => $itemCurrencyCode,
                'value' => $this->formatAmount($recomputedItemTotal)
            ];

            $recomputedTaxTotal = $this->roundToPayPalPrecision($this->calculateTaxTotalFromItems($recomputedItems));
            $taxCurrencyCode = $orderData['breakdown']['tax_total']['currency_code']
                ?? ($recomputedItems[0]['unit_amount']['currency_code']
                    ?? ($orderData['breakdown']['item_total']['currency_code']
                        ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD')));
            $orderData['breakdown']['tax_total'] = [
                'currency_code' => $taxCurrencyCode,
                'value' => $this->formatAmount($recomputedTaxTotal)
            ];
        }

        return $orderData;
    }

    /**
     * Calculate total from items
     * Note: do not round per-item or per-component; only round at final comparison stage.
     */
    private function calculateItemsTotal(array $items, array $breakdown = []): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (int)($item['quantity'] ?? 1);
            $unitAmount = (float)($item['unit_amount']['value'] ?? 0);
            $total += $quantity * ($unitAmount + (float)$item['tax']["value"]);
        }

        $shipping = isset($breakdown['shipping']['value']) ? (float)$breakdown['shipping']['value'] : 0.0;
        $total += $shipping;

        $discount = isset($breakdown['discount']['value']) ? (float)$breakdown['discount']['value'] : 0.0;
        $shippingDiscount = isset($breakdown['shipping_discount']['value'])
            ? (float)$breakdown['shipping_discount']['value'] : 0.0;
        $total -= ($discount + $shippingDiscount);

        return $total;
    }

    private function calculateBreakdownTotal(array $breakdown, ?float $amountValue = null): float
    {
        if ($amountValue !== null) {
            return $this->roundToPayPalPrecision($amountValue);
        }

        $itemTotal        = $this->roundToPayPalPrecision((float)($breakdown['item_total']['value'] ?? 0));
        $shipping         = $this->roundToPayPalPrecision((float)($breakdown['shipping']['value'] ?? 0));
        $taxTotal         = $this->roundToPayPalPrecision((float)($breakdown['tax_total']['value'] ?? 0));
        $handling         = $this->roundToPayPalPrecision((float)($breakdown['handling']['value'] ?? 0));
        $insurance        = $this->roundToPayPalPrecision((float)($breakdown['insurance']['value'] ?? 0));
        $discount         = $this->roundToPayPalPrecision((float)($breakdown['discount']['value'] ?? 0));
        $shippingDiscount = $this->roundToPayPalPrecision((float)($breakdown['shipping_discount']['value'] ?? 0));

        $total = $itemTotal
            + $shipping
            + $taxTotal
            + $handling
            + $insurance
            - $discount
            - $shippingDiscount;

        return $this->roundToPayPalPrecision($total);
    }


    private function calculateTaxTotalFromItems(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 1);
            $taxPerUnit = isset($item['tax']['value']) ? (float)$item['tax']['value'] : 0.0;
            $sum += $qty * $taxPerUnit;
        }
        return $this->roundToPayPalPrecision($sum);
    }

    private function calculateItemTotalFromItems(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 1);
            $unitAmount = isset($item['unit_amount']['value']) ? (float)$item['unit_amount']['value'] : 0.0;
            $sum += $qty * $unitAmount;
        }
        return $this->roundToPayPalPrecision($sum);
    }

    private function adjustAmounts(array $orderData, float $difference): array
    {
        $absoluteDifference = $this->roundToPayPalPrecision(abs($difference));

        if ($absoluteDifference === 0.0) {
            return $orderData;
        }

        if ($difference > 0) {
            $orderData = $this->addShippingDiscount($orderData, $absoluteDifference);
        } else {
            $orderData = $this->addAdjustmentItem($orderData, $absoluteDifference);
        }

        return $orderData;
    }


    private function addShippingDiscount(array $orderData, float $discountValue): array
    {
        if (!isset($orderData['breakdown']['shipping_discount'])) {
            $currencyCode = $orderData['items'][0]['unit_amount']['currency_code']
                ?? ($orderData['breakdown']['item_total']['currency_code']
                    ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
            $orderData['breakdown']['shipping_discount'] = [
                'currency_code' => $currencyCode,
                'value' => '0.00'
            ];
        }

        // Add discount (reduces breakdown total)
        $currentDiscount = (float)$orderData['breakdown']['shipping_discount']['value'];
        $newDiscount = $this->roundToPayPalPrecision($currentDiscount + $discountValue);

        $orderData['breakdown']['shipping_discount']['value'] = $this->formatAmount($newDiscount);

        return $orderData;
    }


    private function addHandling(array $orderData, float $handlingValue): array
    {
        if (!isset($orderData['breakdown']['handling'])) {
            $currencyCode = $orderData['items'][0]['unit_amount']['currency_code']
                ?? ($orderData['breakdown']['item_total']['currency_code']
                    ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
            $orderData['breakdown']['handling'] = [
                'currency_code' => $currencyCode,
                'value' => '0.00'
            ];
        }

        $currentHandling = (float)$orderData['breakdown']['handling']['value'];
        $newHandling = $this->roundToPayPalPrecision($currentHandling + $handlingValue);
        $orderData['breakdown']['handling']['value'] = $this->formatAmount($newHandling);

        return $orderData;
    }


    private function addAdjustmentItem(array $orderData, float $adjustmentValue): array
    {
        $currencyCode = $orderData['items'][0]['unit_amount']['currency_code'] ?? 'USD';

        $adjustmentItem = [
            'name' => 'Rounding Adjustment',
            'quantity' => 1,
            'category' => 'DIGITAL_GOODS',
            'unit_amount' => [
                'currency_code' => $currencyCode,
                'value' => $this->formatAmount($adjustmentValue)
            ],
            'tax' => [
                'currency_code' => $currencyCode,
                'value' => $this->formatAmount(0.0)
            ],
            'tax_rate' => '0'
        ];

        $orderData['items'][] = $adjustmentItem;

        return $orderData;
    }

    private function roundToPayPalPrecision(float $amount): float
    {
        return round($amount, self::MAX_DECIMALS);
    }

    private function formatAmount(float $amount): string
    {
        return AmountFormatter::format($amount, self::DECIMAL_PRECISION);
    }
}
