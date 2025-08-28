<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\tests\Unit\Core;

use OxidSolutionCatalysts\PayPal\Service\PayPalAmountValidator;
use PHPUnit\Framework\TestCase;

class PayPalAmountValidatorTest extends TestCase
{
    public function testValidateAndAdjustOrderCorrectsTaxTotalFromItems(): void
    {
        $validator = new PayPalAmountValidator();

        // Items (EUR):
        // - qty 2, unit 1.00, tax per unit 0.19 => contributes 0.38 tax
        // - qty 1, unit 2.00, tax per unit 0.14 => contributes 0.14 tax
        // Total tax expected = 0.52
        // Items total (for amount_value, to avoid other adjustments):
        // 2 * (1.00 + 0.19) + 1 * (2.00 + 0.14) = 4.52
        $orderData = [
            'items' => [
                [
                    'quantity' => 2,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '1.00'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.19'],
                ],
                [
                    'quantity' => 1,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '2.00'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.14'],
                ],
            ],
            'breakdown' => [
                // Deliberately wrong tax_total and currency
                'tax_total' => ['currency_code' => 'USD', 'value' => '0.00'],
                // Other keys optional; we provide empty or zero values so they don't affect amount
                'item_total' => ['currency_code' => 'EUR', 'value' => '3.00'],
                'shipping' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
            ],
            // Provide amount_value to prevent shipping_discount/adjustment item changes (difference becomes 0)
            'amount_value' => 4.52,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        $this->assertArrayHasKey('breakdown', $adjusted);
        $this->assertArrayHasKey('tax_total', $adjusted['breakdown']);
        $this->assertSame('EUR', $adjusted['breakdown']['tax_total']['currency_code'], 'tax_total currency should be resolved from items');
        $this->assertSame('0.52', $adjusted['breakdown']['tax_total']['value'], 'tax_total should be corrected to sum of per-unit taxes times quantity');

        // Ensure no additional adjustment took place since amount_value matched items total
        $this->assertArrayNotHasKey('shipping_discount', $adjusted['breakdown'] ?? [], 'no shipping_discount expected');
        $this->assertArrayNotHasKey('handling', $adjusted['breakdown'] ?? [], 'no handling expected');
        // Items count should remain the same (no dummy items added when difference is 0)
        $this->assertCount(2, $adjusted['items']);
    }

    public function testValidateAndAdjustOrderCorrectsItemTotalFromItems(): void
    {
        $validator = new PayPalAmountValidator();

        // Items (EUR): qty 2 @ 1.23, qty 3 @ 0.456 =>
        // item_total expected = 2*1.23 + 3*0.456 = 2.46 + 1.368 = 3.828 -> 3.83
        $orderData = [
            'items' => [
                [
                    'quantity' => 2,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '1.23'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                ],
                [
                    'quantity' => 3,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '0.456'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                ],
            ],
            'breakdown' => [
                // Deliberately wrong item_total and currency to be corrected
                'item_total' => ['currency_code' => 'USD', 'value' => '0.00'],
                'tax_total' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'shipping' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
            ],
            // amount_value equals computed items total (no tax, no shipping/discount) => 3.83
            'amount_value' => 3.83,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        $this->assertArrayHasKey('item_total', $adjusted['breakdown']);
        $this->assertSame('EUR', $adjusted['breakdown']['item_total']['currency_code'], 'item_total currency should be resolved from items');
        $this->assertSame('3.83', $adjusted['breakdown']['item_total']['value'], 'item_total should be corrected to sum of unit amounts times quantity');

        // No further adjustments expected
        $this->assertArrayNotHasKey('shipping_discount', $adjusted['breakdown'] ?? [], 'no shipping_discount expected');
        $this->assertArrayNotHasKey('handling', $adjusted['breakdown'] ?? [], 'no handling expected');
    }
    public function testValidateAndAdjustOrderRecalculatesItemTotalAfterAdjustmentItem(): void
    {
        $validator = new PayPalAmountValidator();

        // Start with one item totaling 1.00 and amount_value 1.01 -> requires +0.01 adjustment item
        $orderData = [
            'items' => [
                [
                    'quantity' => 1,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '1.00'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                ],
            ],
            'breakdown' => [
                'item_total' => ['currency_code' => 'EUR', 'value' => '1.00'],
                'tax_total' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'shipping' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
            ],
            'amount_value' => 1.01,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        // Expect a dummy item added (items count = 2) and item_total updated to 1.01
        $this->assertCount(2, $adjusted['items']);
        $this->assertSame('1.01', $adjusted['breakdown']['item_total']['value']);
        $this->assertSame('EUR', $adjusted['breakdown']['item_total']['currency_code']);
        // tax_total should remain 0.00 since adjustment item has zero tax
        $this->assertSame('0.00', $adjusted['breakdown']['tax_total']['value']);
        // No shipping_discount expected in this branch
        $this->assertArrayNotHasKey('shipping_discount', $adjusted['breakdown']);
    }
}
