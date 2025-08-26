<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;

/**
 * PayPalAmountValidator (Service)
 *
 * Responsibilities:
 *  - Use BasketSummaryService (NET mode) and VatOptionsService to derive the expected
 *    purchase unit total from the current basket.
 *  - Compare that expected total against the provided purchaseUnits amount.value and compute
 *    a rounding difference (2 decimals).
 *  - Optionally adjust the provided purchaseUnits breakdown using handling or shipping_discount
 *    to reconcile the difference.
 */
class PayPalAmountValidator
{
    private const DECIMALS = 2;

    /**
     * Calculate the difference between the expected NET total (from basket summary)
     * and the provided purchase unit amount.value.
     *
     * Returns: expectedTotal - amountValue (rounded to 2 decimals).
     */
    public function calculateDifference(\OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest $unit): float
    {
        // amount.value is BRUT value; compute a BRUT items-derived total for a fair comparison
        $amountValue = (float)($unit->amount->value ?? 0.0);
        $itemsBrutTotal = $this->calculateItemsBruttoTotal($unit);
        // Difference is itemsDerivedTotal - amountValue
        return $this->round2($itemsBrutTotal - $amountValue);
    }

    /**
     * Adjust the purchase unit breakdown to match the expected NET total.
     * If expected > amount.value -> add shipping_discount = diff (reduces total);
     * If expected < amount.value -> add handling = abs(diff) (increases total).
     */
    public function adjustPurchaseUnits(\OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest $unit): \OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest
    {
        // Ensure amount and breakdown exist
        if (!isset($unit->amount) || !isset($unit->amount->breakdown)) {
            return $unit;
        }
        // Only adjust when items exist (NET items mode)
        if (empty($unit->items)) {
            return $unit;
        }

        $diff = $this->calculateDifference($unit);
        if ($diff === 0.0) {
            return $unit;
        }

        $currency = $unit->amount->currency_code ??
            ($unit->amount->breakdown->item_total->currency_code ??
            ($unit->amount->breakdown->shipping->currency_code ?? 'USD'));

        if ($diff > 0) {
            // expected is higher than provided amount => reduce breakdown via shipping_discount
            $unit->amount->breakdown->initShippingDiscount();
            $current = (float)($unit->amount->breakdown->shipping_discount->value ?? 0.0);
            $unit->amount->breakdown->shipping_discount->value = $this->round2($current + abs($diff));
            $unit->amount->breakdown->shipping_discount->currency_code = $currency;
        } else {
            // expected is lower than provided amount => increase breakdown via handling
            $unit->amount->breakdown->initHandling();
            $current = (float)($unit->amount->breakdown->handling->value ?? 0.0);
            $unit->amount->breakdown->handling->value = $this->round2($current + abs($diff));
            $unit->amount->breakdown->handling->currency_code = $currency;
        }

        return $unit;
    }

    /**
     * Compute expected NET total using BasketNetSummaryService & VatOptionsService.
     * Formula (NET): items + wrapping + giftcard + payment + shipping - discounts.
     * If blShowVATForPayCharge is enabled, subtract the VAT part of payment costs from shipping portion.
     */
    private function calculateExpectedNetTotal(): float
    {
        /** @var BasketSummaryService $netSvc */
        $netSvc = Registry::get(BasketSummaryService::class);
        $summary = $netSvc->getNetSummary();

        $totals = $summary['totals'] ?? [];
        $items = (float)($totals['items_net_total'] ?? 0.0);
        $shipping = (float)($totals['shipping_net'] ?? 0.0);
        $payment = (float)($totals['payment_net'] ?? 0.0);
        $wrapping = (float)($totals['wrapping_net'] ?? 0.0);
        $giftcard = (float)($totals['giftcard_net'] ?? 0.0);
        $discounts = (float)($totals['discounts_net'] ?? 0.0);

        /** @var VatOptionsService $vatSvc */
        $vatSvc = Registry::get(VatOptionsService::class);
        $paymentVatPercent = (float)($summary['vat']['payment_vat'] ?? 0.0);

        // If VAT in payment is displayed, subtract VAT part (amount) from shipping component for PayPal schema
        if ($paymentVatPercent > 0 && $vatSvc->isPaymentVatVisible()) {
            $paymentVatAmount = ($paymentVatPercent / 100.0) * $payment;
            $shipping = max(0.0, $shipping - $paymentVatAmount);
        }

        $expected = $items + $wrapping + $giftcard + $payment + $shipping - $discounts;
        return $this->round2($expected);
    }

    /**
     * Compute BRUT items-derived total from the model purchase unit for comparison with amount.value
     */
    private function calculateItemsBruttoTotal(\OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest $unit): float
    {
        $total = 0.0;
        // Items (unit amounts are BRUT in our model building)
        if (is_array($unit->items)) {
            foreach ($unit->items as $item) {
                $qty = (float)($item->quantity ?? 1);
                $unitVal = isset($item->unit_amount->value) ? (float)$item->unit_amount->value : 0.0;
                $total += $qty * $unitVal;
            }
        }
        // Shipping (BRUT)
        $shipping = isset($unit->amount->breakdown->shipping->value) ? (float)$unit->amount->breakdown->shipping->value : 0.0;
        // Discounts (BRUT)
        $discount = isset($unit->amount->breakdown->discount->value) ? (float)$unit->amount->breakdown->discount->value : 0.0;
        $shippingDiscount = isset($unit->amount->breakdown->shipping_discount->value) ? (float)$unit->amount->breakdown->shipping_discount->value : 0.0;

        // If shop displays VAT contained in Payment Method Charges, subtract payment VAT from shipping portion
        try {
            /** @var VatOptionsService $vatSvc */
            $vatSvc = Registry::get(VatOptionsService::class);
            if ($vatSvc->isPaymentVatVisible()) {
                $session = Registry::getSession();
                $basket = $session ? $session->getBasket() : null;
                if ($basket) {
                    $paymentVat = (float)$vatSvc->getPaymentVatAmount($basket);
                    if ($paymentVat > 0) {
                        $shipping = max(0.0, $shipping - $paymentVat);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore VAT adjustments on failure
        }

        $total += $shipping;
        $total -= ($discount + $shippingDiscount);

        return $this->round2($total);
    }

    private function round2(float $v): float
    {
        return round($v, self::DECIMALS);
    }

    private function format2(float $v): string
    {
        return number_format($this->round2($v), self::DECIMALS, '.', '');
    }
}
