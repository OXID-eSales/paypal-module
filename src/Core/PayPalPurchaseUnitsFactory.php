<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown as ApiAmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown as ApiAmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item as ApiItem;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Money as ApiMoney;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest as ApiPurchaseUnitRequest;

/**
 * PayPalPurchaseUnitsFactory
 *
 * Responsibility: Build only the PayPal purchase_units array.
 * This class contains all mechanisms required to create purchase units
 * directly from the current basket, without using BasketOrderDataMapper.
 * Other request-related parts (payer, shipping address objects, experience
 * context, etc.) are handled elsewhere.
 */
class PayPalPurchaseUnitsFactory
{
    /**
     * Build purchase_units for the current basket.
     *
     * @param bool $withItems Whether to include individual line items.
     * @return array A plain array structure representing purchase_units.
     */
    public function getPurchaseUnitsArray(bool $withItems = true): array
    {
        $basket = $this->getBasket();

        // Ensure PayPal compatible precision
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        // Build items first (needed to compute tax_total from items)
        $itemsArr = $withItems ? $this->mapItems($basket) : [];
        // Build amount (tax_total will be computed from itemsArr)
        $amountArr = $this->buildAmountArray($basket, $itemsArr);

        // Assemble purchase unit
        $purchaseUnit = [
            'reference_id' => Constants::PAYPAL_ORDER_REFERENCE_ID,
            'amount' => $amountArr,
        ];
        if (!empty($itemsArr)) {
            $purchaseUnit['items'] = $itemsArr;
        }

        // Mirror some breakdown info for convenience (non-API helper fields)
        if (isset($amountArr['breakdown']) && is_array($amountArr['breakdown'])) {
            if (isset($amountArr['breakdown']['shipping']) && is_array($amountArr['breakdown']['shipping'])) {
                $purchaseUnit['shipping_costs'] = [
                    'currency_code' => (string)($amountArr['breakdown']['shipping']['currency_code'] ?? ($amountArr['currency_code'] ?? '')),
                    'value' => (string)($amountArr['breakdown']['shipping']['value'] ?? '0.00'),
                ];
            }
            if (isset($amountArr['breakdown']['discount']) && is_array($amountArr['breakdown']['discount'])) {
                $purchaseUnit['discounts'] = [
                    'currency_code' => (string)($amountArr['breakdown']['discount']['currency_code'] ?? ($amountArr['currency_code'] ?? '')),
                    'value' => (string)($amountArr['breakdown']['discount']['value'] ?? '0.00'),
                ];
            }
        }

        return [ $purchaseUnit ];
    }

    /**
     * Build and return a PurchaseUnitRequest API model for the current basket.
     *
     * @return ApiPurchaseUnitRequest
     */
    public function getPurchaseUnitsObject(bool $withItems = true): ApiPurchaseUnitRequest
    {
        $basket = $this->getBasket();

        // Ensure PayPal compatible precision
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        // First map items (used for tax_total computation)
        $itemsArr = $withItems ? $this->mapItems($basket) : [];
        $amountArr = $this->buildAmountArray($basket, $itemsArr);

        // Map amount array to API models
        $amount = new ApiAmountWithBreakdown([
            'currency_code' => (string)($amountArr['currency_code'] ?? ''),
            'value' => number_format((float)($amountArr['value'] ?? 0.0), 2, '.', ''),
        ]);
        if (!empty($amountArr['breakdown']) && is_array($amountArr['breakdown'])) {
            $amount->breakdown = new ApiAmountBreakdown([
                'shipping' => $amountArr['breakdown']['shipping'] ?? null,
                'discount' => $amountArr['breakdown']['discount'] ?? null,
                'tax_total' => $amountArr['breakdown']['tax_total'] ?? null,
                'item_total' => $amountArr['breakdown']['item_total'] ?? null,
            ]);
        }

        // Map items
        $items = [];
        if (!empty($itemsArr)) {
            foreach ($itemsArr as $it) {
                $apiItem = new ApiItem([
                    'name' => (string)($it['name'] ?? ''),
                    'unit_amount' => [
                        'currency_code' => (string)($it['unit_amount']['currency_code'] ?? ($amount->currency_code ?? '')),
                        'value' => (string)($it['unit_amount']['value'] ?? '0.00'),
                    ],
                    'quantity' => (string)($it['quantity'] ?? '1'),
                    'tax' => isset($it['tax']) ? [
                        'currency_code' => (string)($it['tax']['currency_code'] ?? ($amount->currency_code ?? '')),
                        'value' => (string)($it['tax']['value'] ?? '0.00'),
                    ] : null,
                    'tax_rate' => (string)($it['tax_rate'] ?? '0'),
                    'category' => (string)($it['category'] ?? ''),
                ]);
                $items[] = $apiItem;
            }
        }

        // Assemble unit
        $unit = new ApiPurchaseUnitRequest();
        $unit->reference_id = Constants::PAYPAL_ORDER_REFERENCE_ID;
        $unit->amount = $amount;
        if (!empty($items)) {
            $unit->items = $items;
        }

        return $unit;
    }

    private function getBasket(): Basket
    {
        /** @var Basket $basket */
        $basket = Registry::getSession()->getBasket();
        return $basket;
    }

    /**
     * Build amount with breakdown directly from the basket.
     */
    private function buildAmountArray(Basket $basket, array $itemsForTax = []): array
    {
        $currency = $basket->getBasketCurrency();
        $currency = $currency ? clone $currency : (object)[];
        if (isset($currency->decimal)) {
            $currency->decimal = 2;
        }

        // Total order amount (2 decimals)
        $total = (float) number_format(
            $basket->getPrice()->getBruttoPrice(),
            2,
            '.',
            ''
        );

        // Breakdown components
        $shippingMoney = PriceToMoney::convert($basket->getPayPalCheckoutDeliveryCosts(), $currency);
        $discountMoney = PriceToMoney::convert($basket->getPayPalCheckoutDiscountBrutto(), $currency);
        // tax_total derived from items (per-unit tax * quantity), like OrderRequestFactoryV2::autoFillTaxTotalFromItems
        $taxTotalFloat = $this->sumTaxFromItems($itemsForTax);
        $taxTotalMoney = PriceToMoney::convert($taxTotalFloat, $currency);

        // item_total equals sum(unit_price * quantity) where unit_price respects shop price mode
        $itemTotal = 0.0;
        foreach ((array) $basket->getContents() as $basketItem) {
            $unitPrice = $basketItem->getUnitPrice();
            if ($unitPrice) {
                $qty = (float) $basketItem->getAmount();
                $val = (float) $unitPrice->getPrice();
                $itemTotal += $qty * $val;
            }
        }
        $itemTotalMoney = PriceToMoney::convert($itemTotal, $currency);

        return [
            'value' => $total,
            'currency_code' => $currency->name ?? '',
            'breakdown' => [
                'shipping' => [
                    'currency_code' => $shippingMoney->currency_code,
                    'value' => $shippingMoney->value,
                ],
                'discount' => [
                    'currency_code' => $discountMoney->currency_code,
                    'value' => $discountMoney->value,
                ],
                'tax_total' => [
                    'currency_code' => $taxTotalMoney->currency_code,
                    'value' => $taxTotalMoney->value,
                ],
                'item_total' => [
                    'currency_code' => $itemTotalMoney->currency_code,
                    'value' => $itemTotalMoney->value,
                ],
            ],
        ];
    }

    /**
     * Map basket contents to PayPal items array.
     */
    private function mapItems(Basket $basket): array
    {
        $items = [];
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        $isNetMode = (bool)Registry::getConfig()->getConfigParam('blShowNetPrice');

        /** @var BasketItem $basketItem */
        foreach ($basket->getContents() as $basketItem) {
            if (!($basketItem instanceof BasketItem)) {
                continue;
            }
            $title = (string)$basketItem->getTitle();
            $qty = (string)$basketItem->getAmount();
            $unitPrice = $basketItem->getUnitPrice();
            if (!$unitPrice) {
                continue;
            }

            // Use NET or GROSS depending on shop setting
            $value = $isNetMode ? (float)$unitPrice->getNettoPrice() : (float)$unitPrice->getBruttoPrice();
            if ($value <= 0) {
                continue; // skip zero-price entries
            }

            $unitAmount = PriceToMoney::convert($value, $currency);

            // Determine per-unit tax value depending on pricing mode
            $vatPercent = (float)($unitPrice->getVat() ?? 0.0);
            $taxValueStr = '0.00';
            if ($isNetMode) {
                // In net mode, PayPal expects per-unit tax amount
                $taxPerUnit = $value * ($vatPercent / 100);
                $taxMoney = PriceToMoney::convert($taxPerUnit, $currency);
                $taxValueStr = $taxMoney->value;
            }

            $items[] = [
                'name' => $title,
                'quantity' => $qty,
                'unit_amount' => [
                    'currency_code' => $unitAmount->currency_code,
                    'value' => $unitAmount->value,
                ],
                'tax' => [ 'currency_code' => $unitAmount->currency_code, 'value' => $taxValueStr ],
                'tax_rate' => (string)($unitPrice->getVat() ?? '0'),
                'category' => $this->inferItemCategory($basketItem),
            ];
        }

        // Additional lines (wrapping, gift card, payment, surcharge, rounding diff) can be added here if needed
        return $items;
    }

    private function inferItemCategory(BasketItem $basketItem): string
    {
        try {
            $article = $basketItem->getArticle();
            if ($article && method_exists($article, 'isVirtualPayPalArticle') && $article->isVirtualPayPalArticle()) {
                return \OxidSolutionCatalysts\PayPalApi\Model\Orders\Item::CATEGORY_DIGITAL_GOODS;
            }
        } catch (\Throwable $e) {
            // ignore and fallback below
        }
        return \OxidSolutionCatalysts\PayPalApi\Model\Orders\Item::CATEGORY_PHYSICAL_GOODS;
    }

    /**
     * Ported from OrderRequestFactoryV2::autoFillTaxTotalFromItems logic.
     * Sum per-item tax (per-unit tax * quantity) from items array.
     */
    private function sumTaxFromItems(array $items): float
    {
        if (empty($items)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $qty = isset($it['quantity']) ? (float)$it['quantity'] : 1.0;
            $taxPerUnit = 0.0;
            if (isset($it['tax']) && is_array($it['tax']) && isset($it['tax']['value'])) {
                $taxPerUnit = (float)$it['tax']['value'];
            }
            $sum += $qty * $taxPerUnit;
        }
        // round to two decimals to mimic money rounding
        return round($sum, 2);
    }
}
