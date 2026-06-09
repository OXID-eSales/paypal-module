<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Core;

use OxidSolutionCatalysts\PayPal\Service\PayPalAmountValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the array-based Core PayPalAmountValidator to verify rounding
 * adjustments when item prices have precision above 2 decimals.
 */
class PayPalAmountValidatorTest extends TestCase
{
    /**
     * If items-derived total exceeds amount.value by 0.01 due to >2 decimals,
     * validator should add a shipping_discount of 0.01 to reduce breakdown.
     */
    public function testAddsShippingDiscountWhenItemsSumExceedsAmountByOneCent(): void
    {
        $validator = new PayPalAmountValidator();

        // Items: 1.005 (qty 1) + 2.005 (qty 1) = 3.01 raw; amount.value is 3.00
        // Difference = +0.01 => expect shipping_discount = 0.01
        $orderData = [
            'items' => [
                [
                    'name' => 'A',
                    'quantity' => '1',
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '1.005'],
                ],
                [
                    'name' => 'B',
                    'quantity' => '1',
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '2.005'],
                ],
            ],
            'breakdown' => [
                'item_total' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'shipping'   => ['currency_code' => 'EUR', 'value' => '0.00'],
                'discount'   => ['currency_code' => 'EUR', 'value' => '0.00'],
                'tax_total'  => ['currency_code' => 'EUR', 'value' => '0.00'],
            ],
            'amount_value' => 3.00,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        $this->assertArrayHasKey('breakdown', $adjusted);
        $this->assertArrayHasKey('shipping_discount', $adjusted['breakdown']);
        $this->assertSame('EUR', $adjusted['breakdown']['shipping_discount']['currency_code']);
        $this->assertSame('0.01', $adjusted['breakdown']['shipping_discount']['value']);
        // No handling should be added in this case
        $this->assertArrayNotHasKey('handling', $adjusted['breakdown']);
    }

    /**
     * If items-derived total is below amount.value by 0.01 due to >2 decimals,
     * validator should add a small adjustment item to increase the items total, not handling.
     */
    public function testAddsAdjustmentItemWhenItemsSumBelowAmountByOneCent(): void
    {
        $validator = new PayPalAmountValidator();

        // Items: 1.994 (qty 1) + 1.996 (qty 1) = 3.99 raw; amount.value is 4.00
        // Difference = -0.01 => expect a rounding adjustment item of 0.01 to be added
        $orderData = [
            'items' => [
                [
                    'name' => 'A',
                    'quantity' => '1',
                    'unit_amount' => ['currency_code' => 'USD', 'value' => '1.994'],
                ],
                [
                    'name' => 'B',
                    'quantity' => '1',
                    'unit_amount' => ['currency_code' => 'USD', 'value' => '1.996'],
                ],
            ],
            'breakdown' => [
                'item_total' => ['currency_code' => 'USD', 'value' => '0.00'],
                'shipping'   => ['currency_code' => 'USD', 'value' => '0.00'],
                'discount'   => ['currency_code' => 'USD', 'value' => '0.00'],
                'tax_total'  => ['currency_code' => 'USD', 'value' => '0.00'],
            ],
            'amount_value' => 4.00,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        $this->assertArrayHasKey('breakdown', $adjusted);
        // No handling should be added anymore
        $this->assertArrayNotHasKey('handling', $adjusted['breakdown']);
        // No shipping discount should be added in this case
        $this->assertArrayNotHasKey('shipping_discount', $adjusted['breakdown']);
        // Expect an extra adjustment item to be added and item_total updated to the amount value
        $this->assertCount(3, $adjusted['items']);
        $this->assertSame('4.00', $adjusted['breakdown']['item_total']['value']);
        $this->assertSame('USD', $adjusted['breakdown']['item_total']['currency_code']);
    }

    /**
     * Regression test for AMOUNT_MISMATCH when a payment-method surcharge is used
     * together with "show VAT for payment surcharge" (blShowVATForPayCharge).
     *
     * The surcharge is already contained gross in amount_value, so its VAT part must
     * NOT be subtracted from the items total. Otherwise the compensating adjustment item
     * becomes too large by the surcharge VAT and the breakdown no longer matches amount.
     *
     * Real-world case (gross mode):
     *   item 386.78 + shipping 6.95 + surcharge 0.39 (= 0.33 net) = 394.12 = amount_value
     *   surcharge VAT part = 0.06
     * Buggy behaviour produced an adjustment of 0.45 (=0.39+0.06) and item_total 387.23,
     * so PayPal computed 387.23 + 6.95 = 394.18 != 394.12 -> AMOUNT_MISMATCH.
     */
    public function testPaymentVatInBreakdownDoesNotDistortAdjustment(): void
    {
        $validator = new PayPalAmountValidator();

        $orderData = [
            'items' => [
                [
                    'quantity' => 1,
                    'unit_amount' => ['currency_code' => 'EUR', 'value' => '386.78'],
                    'tax' => ['currency_code' => 'EUR', 'value' => '0.00'],
                ],
            ],
            'breakdown' => [
                'item_total' => ['currency_code' => 'EUR', 'value' => '386.78'],
                'tax_total' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'shipping' => ['currency_code' => 'EUR', 'value' => '6.95'],
                'discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
                // Simulates the previously injected (and faulty) payment VAT value.
                // It must be ignored by the validator now.
                'payment_vat' => 0.06,
            ],
            'amount_value' => 394.12,
        ];

        $adjusted = $validator->validateAndAdjustOrder($orderData);

        // Adjustment item must cover the full gross surcharge (0.39), not 0.45.
        $this->assertCount(2, $adjusted['items']);
        $adjustmentItem = end($adjusted['items']);
        $this->assertSame('0.39', $adjustmentItem['unit_amount']['value']);

        // item_total must be 386.78 + 0.39 = 387.17.
        $this->assertSame('387.17', $adjusted['breakdown']['item_total']['value']);

        // Decisive assertion: breakdown total must equal amount_value.
        $bd = $adjusted['breakdown'];
        $breakdownTotal = (float)$bd['item_total']['value']
            + (float)$bd['shipping']['value']
            + (float)$bd['tax_total']['value']
            - (float)$bd['discount']['value'];
        $this->assertEqualsWithDelta(394.12, $breakdownTotal, 0.001);
    }
}
