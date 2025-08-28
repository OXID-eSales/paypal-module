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
     * validator should add a handling of 0.01 to increase breakdown.
     */
    public function testAddsHandlingWhenItemsSumBelowAmountByOneCent(): void
    {
        $validator = new PayPalAmountValidator();

        // Items: 1.994 (qty 1) + 1.996 (qty 1) = 3.99 raw; amount.value is 4.00
        // Difference = -0.01 => expect handling = 0.01
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
        $this->assertArrayHasKey('handling', $adjusted['breakdown']);
        $this->assertSame('USD', $adjusted['breakdown']['handling']['currency_code']);
        $this->assertSame('0.01', $adjusted['breakdown']['handling']['value']);
        // No shipping discount should be added in this case
        $this->assertArrayNotHasKey('shipping_discount', $adjusted['breakdown']);
    }
}
