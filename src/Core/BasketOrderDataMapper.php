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

/**
 * BasketOrderDataMapper
 *
 * Maps an OXID Basket to a plain orderData array consumable by
 * OrderRequestFactoryV2::createOrder().
 *
 * This class does not create API model objects; instead it prepares a
 * deterministic associative array that can be unit-tested and then converted
 * to PayPal models via OrderRequestFactoryV2.
 */
class BasketOrderDataMapper
{
    /**
     * Create the orderData array from a Basket.
     *
     * Options supports (all optional):
     *  - intent: 'CAPTURE' | 'AUTHORIZE' (default 'CAPTURE')
     *  - custom_id: string|null
     *  - invoice_id: string|null
     *  - description: string|null (purchase unit description)
     *  - with_items: bool|null (default: true when shop is in gross mode)
     *  - auto_adjust_breakdown: bool (default: false) - let V2 factory compute item_total from items if needed
     */
    public function createOrderData(Basket $basket, array $options = []): array
    {
        $intent = (string)($options['intent'] ?? 'CAPTURE');
        $customId = $options['custom_id'] ?? null;
        $invoiceId = $options['invoice_id'] ?? null;
        $description = $options['description'] ?? null;
        $autoAdjust = (bool)($options['auto_adjust_breakdown'] ?? false);

        // Always include items by default; callers can disable by passing ['with_items' => false]
        $withItems = $options['with_items'] ?? true;

        // Currency context (2 decimals supported by PayPal)
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        // Build amount via existing factory to keep parity with current PayPal request logic
        /** @var PayPalRequestAmountFactory $amountFactory */
        $amountFactory = Registry::get(PayPalRequestAmountFactory::class);
        $amountModel = $amountFactory->getAmount($basket);
        $amountArr = $this->modelToArray($amountModel);

        // Build items array directly from basket
        $itemsArr = [];
        if ($withItems) {
            $itemsArr = $this->mapItems($basket);
        }

        // Assemble purchase unit
        $purchaseUnit = [
            'reference_id' => Constants::PAYPAL_ORDER_REFERENCE_ID,
            'amount' => $amountArr,
        ];
        if ($customId !== null) {
            $purchaseUnit['custom_id'] = (string)$customId;
        }
        if ($invoiceId !== null) {
            $purchaseUnit['invoice_id'] = (string)$invoiceId;
        }
        if ($description !== null) {
            $purchaseUnit['description'] = (string)$description;
        }
        if (!empty($itemsArr)) {
            $purchaseUnit['items'] = $itemsArr;
        }

        $orderData = [
            'intent' => $intent,
            'purchase_units' => [ $purchaseUnit ],
        ];

        if ($autoAdjust) {
            $orderData['auto_adjust_breakdown'] = true;
        }

        return $orderData;
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

            $itemArr = [
                'name' => $title,
                'quantity' => $qty,
                'unit_amount' => [
                    'currency_code' => $unitAmount->currency_code,
                    'value' => $unitAmount->value,
                ],
                'tax' => [ 'currency_code' => $unitAmount->currency_code, 'value' => $taxValueStr ],
                // Set tax_rate to the item's VAT percentage instead of a hardcoded 0
                'tax_rate' => (string)($unitPrice->getVat() ?? '0'),
                'category' => $this->inferItemCategory($basketItem),
            ];

            $items[] = $itemArr;
        }

        // Additional lines (wrapping, gift card, payment, surcharge, rounding diff) can be added here
        // if necessary, mimicking OrderRequestFactory::getItems(). For a minimal mapper, we focus on products only.

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
     * Convert a (simple) model object produced by our API layer to array.
     * Uses json encode/decode to retain nested structure.
     */
    private function modelToArray($model): array
    {
        return json_decode(json_encode($model, JSON_UNESCAPED_UNICODE), true) ?: [];
    }
}
