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
    /** @var Basket|null */
    private ?Basket $basket = null;

    public function __construct(?Basket $basket = null)
    {
        $this->basket = $basket;
    }

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
    public function createOrderData(Basket $basket = null, array $options = []): array
    {
        if ($basket !== null) {
            $this->basket = $basket;
        }
        if (!$this->basket) {
            throw new \InvalidArgumentException('BasketOrderDataMapper requires a Basket instance.');
        }

        $intent = (string)($options['intent'] ?? 'CAPTURE');
        $customId = $options['custom_id'] ?? null;
        $invoiceId = $options['invoice_id'] ?? null;
        $description = $options['description'] ?? null;
        $autoAdjust = (bool)($options['auto_adjust_breakdown'] ?? false);

        // Always include items by default; callers can disable by passing ['with_items' => false]
        $withItems = $options['with_items'] ?? true;

        // Currency context (2 decimals supported by PayPal)
        $currency = $this->basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        // Build amount directly here mirroring PayPalRequestAmountFactory logic
        $amountArr = $this->buildAmountArray();

        // Build items array directly from basket
        $itemsArr = [];
        if ($withItems) {
            $itemsArr = $this->mapItems();
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

        // Mirror shipping costs and discounts from amount.breakdown for convenience (non-API helper fields)
        if (isset($amountArr['breakdown']) && is_array($amountArr['breakdown'])) {
            if (isset($amountArr['breakdown']['shipping']) && is_array($amountArr['breakdown']['shipping'])) {
                // Do not collide with 'shipping' address key; use 'shipping_costs'
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
    private function mapItems(): array
    {
        $items = [];
        $currency = $this->basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        $isNetMode = (bool)Registry::getConfig()->getConfigParam('blShowNetPrice');

        /** @var BasketItem $basketItem */
        foreach ($this->basket->getContents() as $basketItem) {
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
     * Build amount with breakdown directly within the mapper, mirroring
     * PayPalRequestAmountFactory::getAmount() and related helpers.
     */
    private function buildAmountArray(): array
    {
        $currency = $this->basket->getBasketCurrency();
        // clone to avoid side effects and ensure 2 decimals for PayPal
        $currency = $currency ? clone $currency : (object)[];
        if (isset($currency->decimal)) {
            $currency->decimal = 2;
        }

        // Total order amount (PayPal expects 2-decimal precision)
        $total = (float) number_format(
            $this->basket->getPrice()->getBruttoPrice(),
            2,
            '.',
            ''
        );

        // Breakdown components
        // shipping and discount come from extended Basket helpers used by the factory
        $shippingMoney = PriceToMoney::convert($this->basket->getPayPalCheckoutDeliveryCosts(), $currency);
        $discountMoney = PriceToMoney::convert($this->basket->getPayPalCheckoutDiscountBrutto(), $currency);
        $taxTotalMoney = PriceToMoney::convert(0, $currency);

        // item_total equals sum(unit_price * quantity) where unit_price respects shop price mode
        $itemTotal = 0.0;
        foreach ((array) $this->basket->getContents() as $basketItem) {
            $unitPrice = $basketItem->getUnitPrice();
            if ($unitPrice) {
                $qty = (float) $basketItem->getAmount();
                $val = (float) $unitPrice->getPrice();
                $itemTotal += $qty * $val;
            }
        }
        $itemTotalMoney = PriceToMoney::convert($itemTotal, $currency);

        $amount = [
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

        return $amount;
    }

}
