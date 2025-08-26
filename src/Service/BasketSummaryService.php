<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;

/**
 * BasketSummaryService
 *
 * Collects all items and relevant cost components from the basket and
 * returns a complete summary calculated in NET mode.
 *
 * Components covered:
 *  - Basket items (unit price net, quantity, item total net per line)
 *  - Gift wrapping (net)
 *  - Gift card (greeting card) (net)
 *  - Payment costs (net)
 *  - Shipping costs (net)
 *  - Discounts (basket discount + vouchers) in net
 *  - Currency and aggregated totals
 *
 * Notes:
 *  - Uses VatOptionsService to fetch VAT related info (e.g., payment VAT percentage option).
 *  - All money values are raw floats in NET. Formatting is left to the caller.
 */
class BasketSummaryService
{
    /**
     * Aggregate both NET and BRUT summaries together with currency and current shop/module VAT options.
     * If no Basket is provided, it will be fetched from the session (same behavior as other methods).
     *
     * Returns structure:
     *  [
     *    'currency' => 'EUR'|string,
     *    'options'  => VatOptionsService::getVatOptions($basket),
     *    'net'      => getNetSummary($basket),
     *    'brut'     => getBrutSummary($basket)
     *  ]
     */
    public function getSummary(?Basket $basket = null): array
    {
        if (!($basket instanceof Basket)) {
            try {
                $session = Registry::getSession();
                $basket = $session ? $session->getBasket() : null;
            } catch (\Throwable $e) {
                $basket = null;
            }
        }

        // Determine currency code
        $currencyCode = 'EUR';
        if ($basket instanceof Basket) {
            $currency = $basket->getBasketCurrency();
            $currencyCode = $currency ? ($currency->name ?? 'EUR') : 'EUR';
        }

        $vatOptionsService = new VatOptionsService();
        return [
            'currency' => $currencyCode,
            'options' => $vatOptionsService->getVatOptions($basket),
            'net' => $this->getNetSummary($basket),
            'brut' => $this->getBrutSummary($basket),
        ];
    }
    /**
     * Build a NET summary for the current basket.
     * If no Basket is provided, it will be fetched from the session.
     */
    public function getNetSummary(?Basket $basket = null): array
    {
        if (!($basket instanceof Basket)) {
            try {
                $session = Registry::getSession();
                $basket = $session ? $session->getBasket() : null;
            } catch (\Throwable $e) {
                $basket = null;
            }
        }

        if (!($basket instanceof Basket)) {
            // Fallback empty summary when no basket is available
            $vatOptions = new VatOptionsService();
            return [
                'currency' => 'EUR',
                'items' => [],
                'totals' => [
                    'items_net_total' => 0.0,
                    'shipping_net' => 0.0,
                    'payment_net' => 0.0,
                    'wrapping_net' => 0.0,
                    'giftcard_net' => 0.0,
                    'discounts_net' => 0.0,
                    'extras_net_total' => 0.0,
                    'grand_net_total' => 0.0,
                ],
                'vat' => [
                    // Use default VAT percentage from shop config as a fallback
                    'payment_vat' => (float)(Registry::getConfig()->getConfigParam('dDefaultVAT') ?? 0.0),
                    'options' => $vatOptions->getVatOptions(null),
                ],
            ];
        }

        $currency = $basket->getBasketCurrency();
        $currencyCode = $currency ? ($currency->name ?? 'EUR') : 'EUR';

        // Items (NET)
        $items = [];
        $itemsNetTotal = 0.0;
        foreach ($basket->getContents() as $basketItem) {
            if (!($basketItem instanceof BasketItem)) {
                continue;
            }
            $qty = (float)$basketItem->getAmount();
            $unitPrice = $basketItem->getUnitPrice(); // \OxidEsales\Eshop\Core\Price
            if (!$unitPrice) {
                continue;
            }
            $unitNet = (float)$unitPrice->getNettoPrice();
            $lineNet = $unitNet * $qty;
            $itemsNetTotal += $lineNet;

            $items[] = [
                'name' => (string)$basketItem->getTitle(),
                'sku' => method_exists($basketItem, 'getArtNr') ? (string)$basketItem->getArtNr() : '',
                'quantity' => $qty,
                'unit_price_net' => $unitNet,
                'total_net' => $lineNet,
            ];
        }
        // Prefer basket-provided NET sum for products to avoid discrepancies
        $itemsNetTotal = (float)$basket->getNettoSum();

        // Costs (NET)
        $shippingNet = $this->getNetFromCost($basket, 'oxdelivery');
        $wrappingNet = $this->getNetFromCost($basket, 'oxwrapping');
        $giftCardNet = $this->getNetFromCost($basket, 'oxgiftcard');

        // Discounts (NET): basket total discount + vouchers NET
        $discountsNet = $this->getDiscountsNet($basket);

        // VAT related (informational)
        $vatOptions = new VatOptionsService();
        // Compute payment VAT percentage (not absolute amount): vat_value / net * 100
        $paymentVatPercent = 0.0;
        try {
            $paymentCost = $basket->getPaymentCost();
            if ($paymentCost) {
                $net = (float)$paymentCost->getNettoPrice();
                $vatVal = (float)$paymentCost->getVatValue();
                if ($net > 0 && $vatVal > 0) {
                    $paymentVatPercent = round(($vatVal / $net) * 100, 2);
                }
            }
        } catch (\Throwable $e) {
            $paymentVatPercent = 0.0;
        }

        // Aggregated totals (NET)
        $extrasNetTotal = $shippingNet + $wrappingNet + $giftCardNet;
        $grandNetTotal = $itemsNetTotal + $extrasNetTotal - $discountsNet;
        // Pure VAT amount calculated using current shop VAT percentage
        $defaultVAT = (float)(Registry::getConfig()->getConfigParam('dDefaultVAT') ?? 0.0);
        $pureVatNet = ($basket->getNettoSum() - $discountsNet) * $defaultVAT / 100;
        // Discount percentage of the pre-discount base (items + shipping + payment + wrapping + giftcard)
        $discountBaseNet = $itemsNetTotal + $shippingNet + $wrappingNet + $giftCardNet;
        $discountsPercentNet = $discountBaseNet > 0 ? round(($discountsNet / $discountBaseNet) * 100, 2) : 0.0;

        return [
            'currency' => $currencyCode,
            'items' => $items,
            'paypal' => [
                //the amount = item_total + tax_total + shipping + handling + insurance - shipping_discount - discount.
                'breakdown_amount' => ($itemsNetTotal + $shippingNet - $discountsNet) * (1 + $defaultVAT / 100),
                // The subtotal for all items. Must equal sum(items[].unit_amount * items[].quantity) and be non-negative.
                'breakdown_item_total' => max(0.0, $itemsNetTotal * (1 + $defaultVAT / 100)),
            ],
            'totals' => [
                'items_net_total' => $itemsNetTotal,
                'shipping_net' => $shippingNet,
                'wrapping_net' => $wrappingNet,
                'giftcard_net' => $giftCardNet,
                'discounts_net' => $discountsNet,
                'discounts_percent' => $discountsPercentNet,
                'extras_net_total' => $extrasNetTotal,
                'grand_net_total' => $grandNetTotal,
                'pure_vat' => $pureVatNet
            ],
            'vat' => [
                // VAT percentage for payment costs
                'payment_vat' => $paymentVatPercent,
                'options' => $vatOptions->getVatOptions($basket),
            ],
        ];
    }

    /**
     * Helper to get NET amount from a cost entry like 'oxdelivery', 'oxpayment', 'oxwrapping', 'oxgiftcard'.
     */
    private function getNetFromCost(Basket $basket, string $type): float
    {
        $cost = $basket->getCosts($type);
        if ($cost) {
            return (float)$cost->getNettoPrice();
        }
        return 0.0;
    }

    /**
     * Compute combined NET discounts: basket total discount + vouchers discount in NET.
     */
    private function getDiscountsNet(Basket $basket): float
    {
        $config = Registry::getConfig();
        $defaultVAT = (float)($config->getConfigParam('dDefaultVAT') ?? 0.0);
        $discountNet = 0.0;

        // Basket total discount (if any) as NET
        $totalDiscount = $basket->getTotalDiscount(); // Price or null
        if ($totalDiscount instanceof Price) {
            $discountNet += (float)$totalDiscount->getNettoPrice();
        }

        // Vouchers: create a Price per voucher and evaluate NET using default VAT
        $vouchers = $basket->getVouchers();
        foreach ($vouchers as $voucher) {
            $voucherPrice = oxNew(Price::class);
            $voucherPrice->setNettoMode(true);
            $voucherPrice->setPrice($voucher->dVoucherdiscount, $defaultVAT);
            $discountNet += (float)$voucherPrice->getNettoPrice();
        }

        return $discountNet;
    }

    /**
     * Build a BRUT (gross) summary for the current basket.
     * If no Basket is provided, it will be fetched from the session.
     *
     * Returns an array similar in structure to getNetSummary(), but with gross values:
     *  - totals keys: items_brut_total, shipping_brut, payment_brut, wrapping_brut,
     *    giftcard_brut, discounts_brut, extras_brut_total, grand_brut_total
     *  - currency and VAT info are included (payment_vat as percentage, options)
     */
    public function getBrutSummary(?Basket $basket = null): array
    {
        if (!($basket instanceof Basket)) {
            try {
                $session = Registry::getSession();
                $basket = $session ? $session->getBasket() : null;
            } catch (\Throwable $e) {
                $basket = null;
            }
        }

        if (!($basket instanceof Basket)) {
            $vatOptions = new VatOptionsService();
            return [
                'currency' => 'EUR',
                'items' => [],
                'totals' => [
                    'items_brut_total' => 0.0,
                    'shipping_brut' => 0.0,
                    'payment_brut' => 0.0,
                    'wrapping_brut' => 0.0,
                    'giftcard_brut' => 0.0,
                    'discounts_brut' => 0.0,
                    'extras_brut_total' => 0.0,
                    'grand_brut_total' => 0.0,
                ],
                'vat' => [
                    // Use default VAT percentage from shop config as a fallback
                    'payment_vat' => (float)(Registry::getConfig()->getConfigParam('dDefaultVAT') ?? 0.0),
                    'options' => $vatOptions->getVatOptions(null),
                ],
            ];
        }

        $currency = $basket->getBasketCurrency();
        $currencyCode = $currency ? ($currency->name ?? 'EUR') : 'EUR';

        // Items (BRUT)
        $items = [];
        $itemsBrutTotal = 0.0;
        foreach ($basket->getContents() as $basketItem) {
            if (!($basketItem instanceof BasketItem)) {
                continue;
            }
            $qty = (float)$basketItem->getAmount();
            $unitPrice = $basketItem->getUnitPrice();
            if (!$unitPrice) {
                continue;
            }
            $unitBrut = (float)$unitPrice->getBruttoPrice();
            $lineBrut = $unitBrut * $qty;
            $itemsBrutTotal += $lineBrut;

            $items[] = [
                'name' => (string)$basketItem->getTitle(),
                'sku' => method_exists($basketItem, 'getArtNr') ? (string)$basketItem->getArtNr() : '',
                'quantity' => $qty,
                'unit_price_brut' => $unitBrut,
                'total_brut' => $lineBrut,
            ];
        }

        // Costs (BRUT)
        $shippingBrut = $this->getBrutFromCost($basket, 'oxdelivery');
        $paymentBrut  = $this->getBrutFromCost($basket, 'oxpayment');
        $wrappingBrut = $this->getBrutFromCost($basket, 'oxwrapping');
        $giftCardBrut = $this->getBrutFromCost($basket, 'oxgiftcard');

        // Discounts (BRUT): basket total discount + vouchers BRUT
        $discountsBrut = $this->getDiscountsBrut($basket);

        // VAT related (informational)
        $vatOptions = new VatOptionsService();
        // For BRUT summary, payment_vat should be the absolute VAT amount taken from basket payment costs
        $paymentVatAmount = 0.0;
        try {
            $paymentCost = $basket->getCosts('oxpayment');
            if ($paymentCost) {
                $paymentVatAmount = (float)$paymentCost->getVatValue();
            }
        } catch (\Throwable $e) {
            $paymentVatAmount = 0.0;
        }

        // Aggregated totals (BRUT)
        $extrasBrutTotal = $shippingBrut + $paymentBrut + $wrappingBrut + $giftCardBrut;
        $grandBrutTotal = $itemsBrutTotal + $extrasBrutTotal - $discountsBrut;
        // Pure VAT amount calculated using current shop VAT percentage (extracted from BRUT)
        $defaultVAT = (float)(Registry::getConfig()->getConfigParam('dDefaultVAT') ?? 0.0);
        $pureVatBrut = 0.0;
        if ($defaultVAT > 0) {
            $pureVatBrut = round($grandBrutTotal * ($defaultVAT / (100.0 + $defaultVAT)), 2);
        }
        // Discount percentage of the pre-discount base (items + shipping + wrapping + giftcard)
        $discountBaseBrut = $itemsBrutTotal + $shippingBrut + $wrappingBrut + $giftCardBrut;
        $discountsPercentBrut = $discountBaseBrut > 0 ? round(($discountsBrut / $discountBaseBrut) * 100, 2) : 0.0;

        return [
            'currency' => $currencyCode,
            'items' => $items,
            'paypal' => [
                //the amount = item_total + tax_total + shipping + handling + insurance - shipping_discount - discount.
                // At this summary stage we consider items + shipping - discounts for BRUT values.
                'breakdown_amount' => ($itemsBrutTotal + $shippingBrut - $discountsBrut),
                // The subtotal for all items. Must equal sum(items[].unit_amount * items[].quantity) and be non-negative.
                'breakdown_item_total' => max(0.0, $itemsBrutTotal),
            ],
            'totals' => [
                'items_brut_total' => $itemsBrutTotal,
                'shipping_brut' => $shippingBrut,
                'payment_brut' => $paymentBrut,
                'wrapping_brut' => $wrappingBrut,
                'giftcard_brut' => $giftCardBrut,
                'discounts_brut' => $discountsBrut,
                'discounts_percent' => $discountsPercentBrut,
                'extras_brut_total' => $extrasBrutTotal,
                'grand_brut_total' => $grandBrutTotal,
                'pure_vat' => $pureVatBrut,
            ],
            'vat' => [
                // VAT amount for payment costs (BRUT summary specific)
                'payment_vat' => $paymentVatAmount,
                'options' => $vatOptions->getVatOptions($basket),
            ],
        ];
    }

    /**
     * Helper to get BRUT amount from a cost entry like 'oxdelivery', 'oxpayment', 'oxwrapping', 'oxgiftcard'.
     */
    private function getBrutFromCost(Basket $basket, string $type): float
    {
        $cost = $basket->getCosts($type);
        if ($cost) {
            return (float)$cost->getBruttoPrice();
        }
        return 0.0;
    }

    /**
     * Compute combined BRUT discounts: basket total discount + vouchers discount in BRUT.
     */
    private function getDiscountsBrut(Basket $basket): float
    {
        $config = Registry::getConfig();
        $defaultVAT = (float)($config->getConfigParam('dDefaultVAT') ?? 0.0);
        $discountBrut = 0.0;

        // Basket total discount (if any) as BRUT
        $totalDiscount = $basket->getTotalDiscount(); // Price or null
        if ($totalDiscount instanceof Price) {
            $discountBrut += (float)$totalDiscount->getBruttoPrice();
        }

        // Vouchers: create a Price per voucher and evaluate BRUT using default VAT
        $vouchers = $basket->getVouchers();
        foreach ($vouchers as $voucher) {
            $voucherPrice = oxNew(Price::class);
            $voucherPrice->setNettoMode(false); // brutto mode
            $voucherPrice->setPrice($voucher->dVoucherdiscount, $defaultVAT);
            $discountBrut += (float)$voucherPrice->getBruttoPrice();
        }

        return $discountBrut;
    }
}
