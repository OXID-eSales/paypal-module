<?php

namespace OxidEsales\EshopCommunity\modules\osc\paypal\src\Core;

use InvalidArgumentException;

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
        // Enrich breakdown using services if Basket is present
        try {
            // Fetch basket directly from session; do not rely on orderData['basket']
            $session = \OxidEsales\Eshop\Core\Registry::getSession();
            $basket = $session ? $session->getBasket() : null;
            if ($basket instanceof \OxidEsales\Eshop\Application\Model\Basket) {
                /** @var \OxidSolutionCatalysts\PayPal\Service\VatOptionsService $vatSvc */
                $vatSvc = \OxidEsales\Eshop\Core\Registry::get(\OxidSolutionCatalysts\PayPal\Service\VatOptionsService::class);
                // Attach payment VAT to breakdown if configured
                if ($vatSvc->isPaymentVatVisible()) {
                    $paymentVat = (float)$vatSvc->getPaymentVatAmount($basket);
                    if ($paymentVat > 0) {
                        $orderData['breakdown']['payment_vat'] = $paymentVat;
                    }
                }
                // Initialize BasketSummaryService for potential NET computations (not directly used here)
                /** @var \OxidSolutionCatalysts\PayPal\Service\BasketSummaryService $netSvc */
                $netSvc = \OxidEsales\Eshop\Core\Registry::get(\OxidSolutionCatalysts\PayPal\Service\BasketSummaryService::class);
                // $netSummary = $netSvc->getNetSummary($basket); // intentionally not used to avoid changing amount.value rules
            }
        } catch (\Throwable $e) {
            // Do not block checkout on service retrieval issues
        }

        // Calculate items total (including subtracting discounts if provided in breakdown)
        $items = $orderData['items'] ?? [];
        $itemsTotal = $this->calculateItemsTotal($items, $orderData['breakdown'] ?? []);

        // If no items are present, we are not in NET items mode; do not adjust amounts
        if (empty($items)) {
            if (isset($orderData['breakdown']['payment_vat'])) {
                unset($orderData['breakdown']['payment_vat']);
            }
            return $orderData;
        }

        // Get breakdown/total from already calculated purchaseUnits amount if provided, otherwise compute
        $amountValue = isset($orderData['amount_value']) ? (float)$orderData['amount_value'] : null;
        $breakdownTotal = $this->calculateBreakdownTotal($orderData['breakdown'] ?? [], $amountValue);

        // Check if adjustment is needed
        $difference = round($itemsTotal - $breakdownTotal, self::DECIMAL_PRECISION);

        if (abs($difference) > 0) {
            $orderData = $this->adjustAmounts($orderData, $difference);
        }

        // Sanitize transient keys we might have received in breakdown (not part of PayPal API)
        if (isset($orderData['breakdown']['payment_vat'])) {
            unset($orderData['breakdown']['payment_vat']);
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
            $total += $quantity * $unitAmount;
        }

        // Include shipping costs if present in breakdown (no intermediate rounding)
        $shipping = isset($breakdown['shipping']['value']) ? (float)$breakdown['shipping']['value'] : 0.0;
        $total += $shipping;

        // Subtract discounts if present in breakdown (no intermediate rounding)
        $discount = isset($breakdown['discount']['value']) ? (float)$breakdown['discount']['value'] : 0.0;
        $shippingDiscount = isset($breakdown['shipping_discount']['value']) ? (float)$breakdown['shipping_discount']['value'] : 0.0;
        $total -= ($discount + $shippingDiscount);

        // If enabled in shop config, subtract VAT contained in Payment Method Charges from shipping
        // We expect factories to inject the numeric amount in breakdown['payment_vat'] when the flag blShowVATForPayCharge is on.
        $paymentVat = isset($breakdown['payment_vat']) ? (float)$breakdown['payment_vat'] : 0.0;
        if ($paymentVat > 0) {
            $total -= $paymentVat;
        }

        // Return raw total; final rounding happens when comparing with amount total.
        return $total;
    }

    /**
     * Calculate total from breakdown
     */
    private function calculateBreakdownTotal(array $breakdown, ?float $amountValue = null): float
    {
        // If an already computed purchaseUnits amount value is provided, prefer it
        if ($amountValue !== null) {
            return $this->roundToPayPalPrecision($amountValue);
        }

        // Fallback: compute from breakdown components if amount value is not provided
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

    /**
     * Adjust amounts to resolve rounding differences
     */
    private function adjustAmounts(array $orderData, float $difference): array
    {
        // Difference is itemsTotal - amountValue
        $absoluteDifference = $this->roundToPayPalPrecision(abs($difference));

        // Adjust for the full absolute difference; round to PayPal precision.
        if ($absoluteDifference === 0.0) {
            return $orderData;
        }

        if ($difference > 0) {
            // Items total is higher than provided amount: decrease breakdown via shipping_discount
            $orderData = $this->addShippingDiscount($orderData, $absoluteDifference);
        } else {
            // Items total is lower than provided amount: increase breakdown via handling
            $orderData = $this->addHandling($orderData, $absoluteDifference);
        }

        return $orderData;
    }

    /**
     * Add shipping discount to balance amounts
     */
    private function addShippingDiscount(array $orderData, float $discountValue): array
    {
        // Initialize shipping discount if not exists
        if (!isset($orderData['breakdown']['shipping_discount'])) {
            $currencyCode = $orderData['items'][0]['unit_amount']['currency_code']
                ?? ($orderData['breakdown']['item_total']['currency_code'] ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
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

    /**
     * Add handling to balance amounts (increases breakdown total)
     */
    private function addHandling(array $orderData, float $handlingValue): array
    {
        if (!isset($orderData['breakdown']['handling'])) {
            $currencyCode = $orderData['items'][0]['unit_amount']['currency_code']
                ?? ($orderData['breakdown']['item_total']['currency_code'] ?? ($orderData['breakdown']['shipping']['currency_code'] ?? 'USD'));
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

    /**
     * Adjust item price to balance amounts
     */
    private function adjustItemPrice(array $orderData, float $adjustmentValue): array
    {
        // Find the first item with sufficient value to adjust
        foreach ($orderData['items'] as &$item) {
            $currentPrice = (float)$item['unit_amount']['value'];

            if ($currentPrice >= $adjustmentValue) {
                $newPrice = $this->roundToPayPalPrecision($currentPrice + $adjustmentValue);
                $item['unit_amount']['value'] = $this->formatAmount($newPrice);
                return $orderData;
            }
        }

        // If no suitable item found, create a small adjustment item
        return $this->addAdjustmentItem($orderData, $adjustmentValue);
    }

    /**
     * Add a small adjustment item for very small amounts
     */
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
            ]
        ];

        $orderData['items'][] = $adjustmentItem;

        return $orderData;
    }

    /**
     * Round amount to PayPal's 2-decimal precision
     */
    private function roundToPayPalPrecision(float $amount): float
    {
        return round($amount, self::MAX_DECIMALS);
    }

    /**
     * Format amount as string with 2 decimals
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, self::DECIMAL_PRECISION, '.', '');
    }
}