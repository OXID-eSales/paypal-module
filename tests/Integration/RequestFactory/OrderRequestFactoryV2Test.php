<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactoryV2;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use PHPUnit\Framework\TestCase;

/**
 * Integration-like tests for the unit-testable OrderRequestFactoryV2.
 * These tests do not require OXID Registry/DI and operate on plain arrays.
 */
class OrderRequestFactoryV2Test extends TestCase
{
    public function testCreateOrderWithMinimalPurchaseUnit(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'TEST-REF',
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => '12.34',
                    'breakdown' => [
                        'item_total' => ['currency_code' => 'EUR', 'value' => '10.00'],
                        'shipping'   => ['currency_code' => 'EUR', 'value' => '2.34'],
                        'discount'   => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_total'  => ['currency_code' => 'EUR', 'value' => '0.00'],
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $this->assertInstanceOf(OrderRequest::class, $order);
        $this->assertSame('CAPTURE', $order->intent);
        $this->assertIsArray($order->purchase_units);
        $this->assertSame('TEST-REF', $order->purchase_units[0]->reference_id);
        $this->assertSame('EUR', $order->purchase_units[0]->amount->currency_code);
        $this->assertSame('12.34', $order->purchase_units[0]->amount->value);
        $this->assertSame('10.00', $order->purchase_units[0]->amount->breakdown->item_total->value);
        $this->assertSame('2.34', $order->purchase_units[0]->amount->breakdown->shipping->value);
    }

    public function testCreateOrderAutoAdjustsItemTotalFromItems(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'AUTHORIZE',
            'auto_adjust_breakdown' => true,
            'purchase_units' => [[
                'reference_id' => 'REF-2',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '11.00',
                    // No item_total provided on purpose; it should be calculated from items
                    'breakdown' => [
                        'shipping'   => ['currency_code' => 'USD', 'value' => '1.00'],
                        'discount'   => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_total'  => ['currency_code' => 'USD', 'value' => '0.00'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'Prod A',
                        'quantity' => '2',
                        'unit_amount' => ['currency_code' => 'USD', 'value' => '5.00'],
                        'tax' => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $this->assertSame('AUTHORIZE', $order->intent);
        $unit = $order->purchase_units[0];
        $this->assertSame('REF-2', $unit->reference_id);
        // item_total must be calculated as 2 * 5.00 = 10.00
        $this->assertSame('10.00', $unit->amount->breakdown->item_total->value);
        $this->assertSame('11.00', $unit->amount->value);
        // Basic integrity of item
        $this->assertIsArray($unit->items);
        $this->assertSame('Prod A', $unit->items[0]->name);
        $this->assertSame('2', $unit->items[0]->quantity);
        $this->assertSame('5.00', $unit->items[0]->unit_amount->value);
    }

    public function testCreateOrderWithDiscountsPreserved(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'REF-DISC',
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => '10.50',
                    'breakdown' => [
                        'item_total' => ['currency_code' => 'EUR', 'value' => '10.00'],
                        'shipping'   => ['currency_code' => 'EUR', 'value' => '2.00'],
                        'discount'   => ['currency_code' => 'EUR', 'value' => '1.50'],
                        'tax_total'  => ['currency_code' => 'EUR', 'value' => '0.00'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'Item X',
                        'quantity' => '1',
                        'unit_amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
                        'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $unit = $order->purchase_units[0];
        $this->assertSame('10.50', $unit->amount->value);
        $this->assertSame('10.00', $unit->amount->breakdown->item_total->value);
        $this->assertSame('2.00', $unit->amount->breakdown->shipping->value);
        $this->assertSame('1.50', $unit->amount->breakdown->discount->value);
    }

    public function testAutoAdjustItemTotalWithDiscountsDoesNotChangeDiscount(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'CAPTURE',
            'auto_adjust_breakdown' => true,
            'purchase_units' => [[
                'reference_id' => 'REF-DISC-2',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '10.50',
                    'breakdown' => [
                        'shipping'   => ['currency_code' => 'USD', 'value' => '1.00'],
                        'discount'   => ['currency_code' => 'USD', 'value' => '0.50'],
                        'tax_total'  => ['currency_code' => 'USD', 'value' => '0.00'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'Prod B',
                        'quantity' => '2',
                        'unit_amount' => ['currency_code' => 'USD', 'value' => '5.00'],
                        'tax' => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $unit = $order->purchase_units[0];
        // item_total should be auto-computed: 2 * 5.00 = 10.00
        $this->assertSame('10.00', $unit->amount->breakdown->item_total->value);
        // discount must remain unchanged
        $this->assertSame('0.50', $unit->amount->breakdown->discount->value);
        // amount.value remains as provided
        $this->assertSame('10.50', $unit->amount->value);
    }

    public function testAutoAdjustItemTotalWithHighPrecisionItems(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'CAPTURE',
            'auto_adjust_breakdown' => true,
            'purchase_units' => [[
                'reference_id' => 'REF-HIGH-PREC',
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => '8.39',
                    // No item_total provided to force auto calculation from items
                    'breakdown' => [
                        'shipping'   => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'discount'   => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_total'  => ['currency_code' => 'EUR', 'value' => '0.00'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'HP A',
                        'quantity' => '3',
                        // More than 2 decimals; factory rounds unit_amount to 2 decimals when mapping
                        'unit_amount' => ['currency_code' => 'EUR', 'value' => '1.2345'],
                        'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                    [
                        'name' => 'HP B',
                        'quantity' => '2',
                        'unit_amount' => ['currency_code' => 'EUR', 'value' => '2.3456'],
                        'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $unit = $order->purchase_units[0];
        $this->assertSame('REF-HIGH-PREC', $unit->reference_id);
        // Unit amounts must be rounded to 2 decimals in the model
        $this->assertSame('1.23', $unit->items[0]->unit_amount->value);
        $this->assertSame('2.35', $unit->items[1]->unit_amount->value);
        // item_total must be calculated as 3*1.23 + 2*2.35 = 8.39
        $this->assertSame('8.39', $unit->amount->breakdown->item_total->value);
        // amount.value remains as provided
        $this->assertSame('8.39', $unit->amount->value);
    }

    public function testCreateOrderWithItemsInNetMode(): void
    {
        $factory = new OrderRequestFactoryV2();

        // Simulate net (netto) mode where item unit_amount is net and tax is provided separately per item
        $orderData = [
            'intent' => 'CAPTURE',
            'auto_adjust_breakdown' => true, // let factory populate item_total from net unit amounts
            'purchase_units' => [[
                'reference_id' => 'REF-NET',
                'amount' => [
                    'currency_code' => 'EUR',
                    // Total = item_total (net) 30.00 + shipping 5.00 + tax_total 5.70 - discount 0.70 = 40.00
                    'value' => '40.00',
                    'breakdown' => [
                        // item_total will be auto-calculated from items (2*10.00 + 1*10.00 = 30.00)
                        'shipping'   => ['currency_code' => 'EUR', 'value' => '5.00'],
                        'discount'   => ['currency_code' => 'EUR', 'value' => '0.70'],
                        'tax_total'  => ['currency_code' => 'EUR', 'value' => '5.70'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'Net Prod 1',
                        'quantity' => '2',
                        // Net unit amount
                        'unit_amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
                        // Tax per unit: 19% of 10.00 = 1.90, for quantity 2 total 3.80
                        'tax' => ['currency_code' => 'EUR', 'value' => '1.90'],
                        'tax_rate' => '19',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                    [
                        'name' => 'Net Prod 2',
                        'quantity' => '1',
                        'unit_amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
                        // Tax per unit: 19% of 10.00 = 1.90, for quantity 1 total 1.90
                        'tax' => ['currency_code' => 'EUR', 'value' => '1.90'],
                        'tax_rate' => '19',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $unit = $order->purchase_units[0];
        $this->assertSame('REF-NET', $unit->reference_id);

        // Unit amounts should remain net
        $this->assertSame('10.00', $unit->items[0]->unit_amount->value);
        $this->assertSame('10.00', $unit->items[1]->unit_amount->value);

        // Per-item tax must be preserved (per unit), tax_rate too
        $this->assertSame('1.90', $unit->items[0]->tax->value);
        $this->assertSame('19', $unit->items[0]->tax_rate);
        $this->assertSame('1.90', $unit->items[1]->tax->value);
        $this->assertSame('19', $unit->items[1]->tax_rate);

        // item_total must be computed from net amounts: 2*10.00 + 1*10.00 = 30.00
        $this->assertSame('30.00', $unit->amount->breakdown->item_total->value);
        // tax_total must remain as provided: 3.80 + 1.90 = 5.70; we assert the exact provided value to ensure net-mode behavior relies on given tax_total
        $this->assertSame('5.70', $unit->amount->breakdown->tax_total->value);

        // Total amount integrity check
        $this->assertSame('40.00', $unit->amount->value);
    }

    public function testAutoAdjustOverridesProvidedIncorrectItemTotal(): void
    {
        $factory = new OrderRequestFactoryV2();

        $orderData = [
            'intent' => 'CAPTURE',
            'auto_adjust_breakdown' => true,
            'purchase_units' => [[
                'reference_id' => 'REF-OVERRIDE',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '21.00',
                    'breakdown' => [
                        // Incorrect on purpose; should be overridden to 20.00
                        'item_total' => ['currency_code' => 'USD', 'value' => '99.99'],
                        'shipping'   => ['currency_code' => 'USD', 'value' => '1.00'],
                        'discount'   => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_total'  => ['currency_code' => 'USD', 'value' => '0.00'],
                    ],
                ],
                'items' => [
                    [
                        'name' => 'P1',
                        'quantity' => '2',
                        'unit_amount' => ['currency_code' => 'USD', 'value' => '5.00'],
                        'tax' => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                    [
                        'name' => 'P2',
                        'quantity' => '2',
                        'unit_amount' => ['currency_code' => 'USD', 'value' => '5.00'],
                        'tax' => ['currency_code' => 'USD', 'value' => '0.00'],
                        'tax_rate' => '0',
                        'category' => Item::CATEGORY_PHYSICAL_GOODS,
                    ],
                ],
            ]],
        ];

        $order = $factory->createOrder($orderData);
        $unit = $order->purchase_units[0];
        // item_total should be overridden to the computed sum: 4 * 5.00 = 20.00
        $this->assertSame('20.00', $unit->amount->breakdown->item_total->value);
        // amount.value remains as provided
        $this->assertSame('21.00', $unit->amount->value);
    }
}
