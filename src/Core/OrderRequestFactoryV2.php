<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable3;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Payer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PhoneWithType;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest;
use OxidSolutionCatalysts\PayPal\Service\VatOptionsService;

/**
 * OrderRequestFactoryV2
 *
 * A unit-testable factory that builds a PayPal OrderRequest (Orders v2)
 * from plain arrays without accessing global Registry, session or basket.
 *
 * Input structure (example):
 *  [
 *    'intent' => 'CAPTURE'|'AUTHORIZE',
 *    'payer' => [ 'email_address' => '...', 'name' => ['given_name' => '...', 'surname' => '...'], ... ],
 *    'purchase_units' => [
 *       [
 *         'reference_id' => '...',
 *         'custom_id' => '...',
 *         'invoice_id' => '...',
 *         'description' => '...',
 *         'amount' => [
 *            'currency_code' => 'EUR',
 *            'value' => '10.00',
 *            'breakdown' => [
 *               'item_total' => ['currency_code' => 'EUR', 'value' => '9.00'],
 *               'shipping' => ['currency_code' => 'EUR', 'value' => '2.00'],
 *               'discount' => ['currency_code' => 'EUR', 'value' => '1.00'],
 *               'shipping_discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
 *               'handling' => ['currency_code' => 'EUR', 'value' => '0.00'],
 *               'insurance' => ['currency_code' => 'EUR', 'value' => '0.00'],
 *               'tax_total' => ['currency_code' => 'EUR', 'value' => '0.00'],
 *            ]
 *         ],
 *         'items' => [
 *            [
 *              'name' => 'Product',
 *              'quantity' => '2',
 *              'unit_amount' => ['currency_code' => 'EUR', 'value' => '4.50'],
 *              'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
 *              'tax_rate' => '0',
 *              'category' => Item::CATEGORY_PHYSICAL_GOODS
 *            ]
 *         ],
 *         'shipping' => [
 *           'name' => ['full_name' => 'John Doe'],
 *           'address' => ['address_line_1' => 'Street 1', 'admin_area_2' => 'City', 'postal_code' => '12345', 'country_code' => 'DE']
 *         ]
 *       ]
 *    ]
 *  ]
 */
class OrderRequestFactoryV2
{
    use ServiceContainer;

    private const DECIMALS = 2;

    /**
     * Create an OrderRequest from a plain array payload.
     * This method does not access any global state and is safe for unit testing.
     */
    public function createOrder(array $orderData): OrderRequest
    {
        $request = new OrderRequest();

        $intent = (string)($orderData['intent'] ?? 'CAPTURE');
        if ($intent !== 'CAPTURE' && $intent !== 'AUTHORIZE') {
            throw new InvalidArgumentException('intent must be CAPTURE or AUTHORIZE');
        }
        $request->intent = $intent;

        if (!empty($orderData['payer']) && is_array($orderData['payer'])) {
            $request->payer = $this->mapPayer($orderData['payer']);
        }

        $purchaseUnitsData = $orderData['purchase_units'] ?? [];
        if (!is_array($purchaseUnitsData) || empty($purchaseUnitsData)) {
            throw new InvalidArgumentException('purchase_units must be a non-empty array');
        }
        $request->purchase_units = $this->mapPurchaseUnits($purchaseUnitsData, (bool)($orderData['auto_adjust_breakdown'] ?? true));

        // payment_source and other optional fields could be mapped by the caller in tests as needed
        return $request;
    }

    /**
     * Map purchase units array to model objects.
     * Optionally auto-adjust breakdown item_total when items are present and item_total missing.
     */
    private function mapPurchaseUnits(array $units, bool $autoAdjustBreakdown): array
    {
        $result = [];
        foreach ($units as $unitArr) {
            $unit = new PurchaseUnitRequest();
            if (isset($unitArr['reference_id'])) { $unit->reference_id = (string)$unitArr['reference_id']; }
            if (isset($unitArr['custom_id'])) { $unit->custom_id = (string)$unitArr['custom_id']; }
            if (isset($unitArr['invoice_id'])) { $unit->invoice_id = (string)$unitArr['invoice_id']; }
            if (isset($unitArr['description'])) { $unit->description = (string)$unitArr['description']; }

            if (!isset($unitArr['amount']) || !is_array($unitArr['amount'])) {
                throw new InvalidArgumentException('Each purchase unit requires an amount');
            }
            $unit->amount = $this->mapAmountWithBreakdown($unitArr['amount']);

            // Items
            if (!empty($unitArr['items']) && is_array($unitArr['items'])) {
                $unit->items = [];
                foreach ($unitArr['items'] as $itemArr) {
                    $unit->items[] = $this->mapItem($itemArr);
                }

                // If item_total is missing but items provided, compute it as sum(items[].unit_amount * quantity)
                if ($autoAdjustBreakdown) {
                    $this->autoFillTaxTotalFromItems($unit); // this should be refined. Unit should have taxes correctly filled from the start
                }
            }

            // Shipping costs: map into amount.breakdown.shipping
            if (!empty($unitArr['shipping_costs']) && is_array($unitArr['shipping_costs'])) {
                if (!isset($unit->amount->breakdown)) {
                    $unit->amount->breakdown = new AmountBreakdown();
                }
                $unit->amount->breakdown->shipping = $this->mapShipping($unitArr['shipping_costs']);
            }

            /*// Optional: run array-based Core PayPalAmountValidator to reconcile tiny rounding diffs
            try {
                $breakdownArr = json_decode(json_encode($unit->amount->breakdown), true) ?: [];
                $itemsArr = [];
                if (is_array($unit->items)) {
                    foreach ($unit->items as $it) {
                        $itemsArr[] = [
                            'name' => $it->name ?? '',
                            'quantity' => (string)($it->quantity ?? '1'),
                            'unit_amount' => [
                                'currency_code' => $it->unit_amount->currency_code ?? ($unit->amount->currency_code ?? 'USD'),
                                'value' => $it->unit_amount->value ?? '0.00',
                            ],
                        ];
                    }
                }
                $orderData = [
                    'items' => $itemsArr,
                    'breakdown' => $breakdownArr,
                    'amount_value' => isset($unit->amount->value) ? (float)$unit->amount->value : null,
                ];
                $validator = new \OxidEsales\EshopCommunity\modules\osc\paypal\src\Core\PayPalAmountValidator();
                $adjusted = $validator->validateAndAdjustOrder($orderData);
                if (isset($adjusted['breakdown']['shipping_discount']['value'])) {
                    if (!isset($unit->amount->breakdown)) {
                        $unit->amount->breakdown = new AmountBreakdown();
                    }
                    $unit->amount->breakdown->initShippingDiscount();
                    $unit->amount->breakdown->shipping_discount->value = $adjusted['breakdown']['shipping_discount']['value'];
                    $unit->amount->breakdown->shipping_discount->currency_code = $adjusted['breakdown']['shipping_discount']['currency_code'] ?? ($unit->amount->currency_code ?? 'USD');
                }
                if (isset($adjusted['breakdown']['handling']['value'])) {
                    if (!isset($unit->amount->breakdown)) {
                        $unit->amount->breakdown = new AmountBreakdown();
                    }
                    $unit->amount->breakdown->initHandling();
                    $unit->amount->breakdown->handling->value = $adjusted['breakdown']['handling']['value'];
                    $unit->amount->breakdown->handling->currency_code = $adjusted['breakdown']['handling']['currency_code'] ?? ($unit->amount->currency_code ?? 'USD');
                }
            } catch (\Throwable $e) {
                // Keep unit as-is if validator is not available
            }*/

            // After all auto adjustments, ensure amount.value equals the sum of breakdown components
            //harmles because the unit->amount->value shoudl have basket total gross value
            //if ($autoAdjustBreakdown && isset($unit->amount) && isset($unit->amount->breakdown)) {
            //    $unit->amount->value = $this->toMoneyValue($this->sumBreakdown($unit->amount->breakdown));
            //}

            $result[] = $unit;
        }
        return $result;
    }

    private function mapAmountWithBreakdown(array $amountArr): AmountWithBreakdown
    {
        $amount = new AmountWithBreakdown();
        $amount->currency_code = (string)($amountArr['currency_code'] ?? 'USD');
        $amount->value = $this->toMoneyValue((float)($amountArr['value'] ?? 0.0));

        if (!empty($amountArr['breakdown']) && is_array($amountArr['breakdown'])) {
            $amount->breakdown = $this->mapAmountBreakdown($amountArr['breakdown'], $amount->currency_code);
        }
        return $amount;
    }

    private function mapAmountBreakdown(array $bdArr, string $currency): AmountBreakdown
    {
        $bd = new AmountBreakdown();
        // Known components per PayPal docs
        foreach (['item_total','shipping','tax_total','handling','insurance','shipping_discount','discount'] as $key) {
            if (isset($bdArr[$key]) && is_array($bdArr[$key])) {
                $money = new \stdClass();
                $money->currency_code = (string)($bdArr[$key]['currency_code'] ?? $currency);
                $money->value = $this->toMoneyValue((float)($bdArr[$key]['value'] ?? 0.0));
                $bd->{$key} = $money;
            }
        }
        return $bd;
    }

    private function mapItem(array $itemArr): Item
    {
        $item = new Item();
        if (isset($itemArr['name'])) { $item->name = (string)$itemArr['name']; }
        if (isset($itemArr['sku'])) { $item->sku = (string)$itemArr['sku']; }
        if (isset($itemArr['description'])) { $item->description = (string)$itemArr['description']; }
        if (isset($itemArr['category'])) { $item->category = (string)$itemArr['category']; }

        $qty = (string)($itemArr['quantity'] ?? '1');
        $item->quantity = $qty;

        if (!empty($itemArr['unit_amount']) && is_array($itemArr['unit_amount'])) {
            $money = new \stdClass();
            $money->currency_code = (string)($itemArr['unit_amount']['currency_code'] ?? 'USD');
            $money->value = $this->toMoneyValue((float)($itemArr['unit_amount']['value'] ?? 0.0));
            $item->unit_amount = $money;
        }

        if (!empty($itemArr['tax']) && is_array($itemArr['tax'])) {
            $money = new \stdClass();
            $money->currency_code = (string)($itemArr['tax']['currency_code'] ?? ($item->unit_amount->currency_code ?? 'USD'));
            $money->value = $this->toMoneyValue((float)($itemArr['tax']['value'] ?? 0.0));
            $item->tax = $money;
        }

        if (isset($itemArr['tax_rate'])) { $item->tax_rate = (string)$itemArr['tax_rate']; }
        return $item;
    }  

    private function mapShipping(array $shippingArr): \stdClass
    {
        // Map shipping costs money object: ['currency_code' => 'EUR', 'value' => '2.34']
        $money = new \stdClass();
        $money->currency_code = (string)($shippingArr['currency_code'] ?? 'USD');
        $money->value = $this->toMoneyValue((float)($shippingArr['value'] ?? 0.0));
        return $money;
    }

    private function mapPayer(array $payerArr): Payer
    {
        $payer = new Payer();
        foreach (['email_address','payer_id'] as $k) {
            if (isset($payerArr[$k])) { $payer->{$k} = (string)$payerArr[$k]; }
        }
        if (!empty($payerArr['name']) && is_array($payerArr['name'])) {
            $payer->name = (object) [
                'given_name' => (string)($payerArr['name']['given_name'] ?? ''),
                'surname' => (string)($payerArr['name']['surname'] ?? ''),
            ];
        }
        if (!empty($payerArr['phone']) && is_array($payerArr['phone'])) {
            $phone = new ApiModelPhone();
            $phone->phone_number = (object) ['national_number' => (string)($payerArr['phone']['national_number'] ?? '')];
            $payer->phone = $phone;
        }
        if (!empty($payerArr['phone_with_type']) && is_array($payerArr['phone_with_type'])) {
            $pwt = new PhoneWithType();
            $pwt->phone_type = (string)($payerArr['phone_with_type']['phone_type'] ?? 'MOBILE');
            $pwt->phone_number = (object) ['national_number' => (string)($payerArr['phone_with_type']['national_number'] ?? '')];
            $payer->phone = $pwt; // uses union type in API models
        }
        if (!empty($payerArr['address']) && is_array($payerArr['address'])) {
            $addr = new AddressPortable3();
            foreach ([
                'address_line_1','address_line_2','admin_area_1','admin_area_2','postal_code','country_code'
            ] as $k) {
                if (isset($payerArr['address'][$k])) {
                    $addr->{$k} = (string)$payerArr['address'][$k];
                }
            }
            $payer->address = $addr;
        }
        return $payer;
    }

    /**
     * Ensure breakdown.tax_total equals the sum of per-item tax (per-unit tax * quantity).
     * Only applied when auto_adjust_breakdown = true by caller of mapPurchaseUnits.
     */
    private function autoFillTaxTotalFromItems(PurchaseUnitRequest $unit): void
    {
        if (!is_array($unit->items) || empty($unit->items)) {
            return;
        }
        $sum = 0.0;
        $currency = $unit->amount->currency_code ?? 'USD';
        foreach ($unit->items as $it) {
            $qty = (float)($it->quantity ?? 1);
            $taxPerUnit = 0.0;
            if (isset($it->tax) && isset($it->tax->value)) {
                $taxPerUnit = (float)$it->tax->value;
            }
            $sum += $qty * $taxPerUnit;
        }
        $sum = $this->round2($sum);
        if (!isset($unit->amount->breakdown)) {
            $unit->amount->breakdown = new AmountBreakdown();
        }
        // Always set/override tax_total to reflect item taxes sum
        $unit->amount->breakdown->tax_total = (object) [
            'currency_code' => $currency,
            'value' => $this->toMoneyValue($sum),
        ];
    }

    private function round2(float $v): float
    {
        return round($v, self::DECIMALS);
    }

    private function toMoneyValue(float $v): string
    {
        return number_format($this->round2($v), self::DECIMALS, '.', '');
    }

    /**
     * Resolve VAT percent exclusively via VatOptionsService.
     * No fallback to item tax_rate or other heuristics is applied.
     */
    private function resolveVatPercent(): float
    {
        return $this->getServiceFromContainer(VatOptionsService::class)->getDefaultVatRate();
    }

    /**
     * Sum breakdown components according to PayPal formula:
     * item_total + tax_total + shipping + handling + insurance - shipping_discount - discount
     */
    private function sumBreakdown(AmountBreakdown $bd): float
    {
        $get = function($obj, string $prop): float {
            return isset($obj->{$prop}) && isset($obj->{$prop}->value) ? (float)$obj->{$prop}->value : 0.0;
        };
        $itemTotal = $get($bd, 'item_total');
        $shipping = $get($bd, 'shipping');
        $handling = $get($bd, 'handling');
        $insurance = $get($bd, 'insurance');
        $discount = $get($bd, 'discount');
        $shippingDiscount = $get($bd, 'shipping_discount');

        // Calculate VAT amount from discount
        $discountVatAmount = $discount * ($this->resolveVatPercent() / 100.0);
        $taxTotal = $get($bd, 'tax_total') - $discountVatAmount;

        $sum = $itemTotal + $taxTotal + $shipping + $handling + $insurance - $shippingDiscount - $discount;
        return $this->round2($sum);
    }
}
