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
 * BasketNetSummaryService
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
 *  - Uses VatOptionsService to fetch VAT related info (e.g., payment VAT amount).
 *  - All money values are raw floats in NET. Formatting is left to the caller.
 */
class BasketNetSummaryService
{
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

        // Costs (NET)
        $shippingNet = $this->getNetFromCost($basket, 'oxdelivery');
        $paymentNet  = $this->getNetFromCost($basket, 'oxpayment');
        $wrappingNet = $this->getNetFromCost($basket, 'oxwrapping');
        $giftCardNet = $this->getNetFromCost($basket, 'oxgiftcard');

        // Discounts (NET): basket total discount + vouchers NET
        $discountsNet = $this->getDiscountsNet($basket);

        // VAT related (informational)
        $vatOptions = new VatOptionsService();
        // Compute payment VAT percentage (not absolute amount): vat_value / net * 100
        $paymentVatPercent = 0.0;
        try {
            $paymentCost = $basket->getCosts('oxpayment');
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
        $extrasNetTotal = $shippingNet + $paymentNet + $wrappingNet + $giftCardNet;
        $grandNetTotal = $itemsNetTotal + $extrasNetTotal - $discountsNet;

        return [
            'currency' => $currencyCode,
            'items' => $items,
            'totals' => [
                'items_net_total' => $itemsNetTotal,
                'shipping_net' => $shippingNet,
                'payment_net' => $paymentNet,
                'wrapping_net' => $wrappingNet,
                'giftcard_net' => $giftCardNet,
                'discounts_net' => $discountsNet,
                'extras_net_total' => $extrasNetTotal,
                'grand_net_total' => $grandNetTotal,
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
}
