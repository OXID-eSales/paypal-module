<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Money;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'name' => 'Test Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '2',
            'tax_rate' => '0.08',
            'description' => 'A test item',
            'sku' => 'TEST-SKU-001',
            'category' => 'PHYSICAL_GOODS'
        ];
        
        $item = new Item($data);
        
        $this->assertEquals('Test Item', $item->name);
        $this->assertInstanceOf(Money::class, $item->unit_amount);
        $this->assertEquals('USD', $item->unit_amount->currency_code);
        $this->assertEquals('100.00', $item->unit_amount->value);
        $this->assertEquals('2', $item->quantity);
        $this->assertEquals('0.08', $item->tax_rate);
        $this->assertEquals('A test item', $item->description);
        $this->assertEquals('TEST-SKU-001', $item->sku);
        $this->assertEquals('PHYSICAL_GOODS', $item->category);
    }

    public function testConstructorWithMinimalRequiredData(): void
    {
        $data = [
            'name' => 'Minimal Item',
            'unit_amount' => [
                'currency_code' => 'EUR',
                'value' => '50.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ];
        
        $item = new Item($data);
        
        $this->assertEquals('Minimal Item', $item->name);
        $this->assertInstanceOf(Money::class, $item->unit_amount);
        $this->assertEquals('1', $item->quantity);
        $this->assertEquals('0.05', $item->tax_rate);
        $this->assertNull($item->tax);
        $this->assertNull($item->description);
        $this->assertNull($item->sku);
        $this->assertNull($item->category);
    }

    public function testConstructorWithTaxAmount(): void
    {
        $data = [
            'name' => 'Item with Tax',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'tax' => [
                'currency_code' => 'USD',
                'value' => '8.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.08'
        ];
        
        $item = new Item($data);
        
        $this->assertEquals('Item with Tax', $item->name);
        $this->assertInstanceOf(Money::class, $item->tax);
        $this->assertEquals('USD', $item->tax->currency_code);
        $this->assertEquals('8.00', $item->tax->value);
    }

    public function testConstructorWithNoData(): void
    {
        $item = new Item();
        
        $this->assertNull($item->name);
        $this->assertInstanceOf(Money::class, $item->unit_amount);
        $this->assertNull($item->tax);
        $this->assertNull($item->quantity);
        $this->assertNull($item->tax_rate);
        $this->assertNull($item->description);
        $this->assertNull($item->sku);
        $this->assertNull($item->category);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $item = new Item([
            'name' => 'Test Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ]);
        
        $result = $item->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'name'));
        $this->assertTrue(property_exists($result, 'unit_amount'));
        $this->assertTrue(property_exists($result, 'quantity'));
        $this->assertTrue(property_exists($result, 'tax_rate'));
        $this->assertFalse(property_exists($result, 'tax'));
        $this->assertFalse(property_exists($result, 'description'));
        $this->assertFalse(property_exists($result, 'sku'));
        $this->assertFalse(property_exists($result, 'category'));
    }

    public function testValidateWithValidData(): void
    {
        $item = new Item([
            'name' => 'Valid Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ]);
        
        // Should not throw exception
        $item->validate();
        $this->assertTrue(true);
    }

    public function testValidateNameRequired(): void
    {
        $item = new Item([
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Item must not be NULL');
        
        $item->validate();
    }

    public function testValidateNameTooShort(): void
    {
        $item = new Item([
            'name' => '', // Empty string, min is 1
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Item must have minlength of 1');
        
        $item->validate();
    }

    public function testValidateNameTooLong(): void
    {
        $item = new Item([
            'name' => str_repeat('a', 128), // 128 characters, max is 127
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Item must have maxlength of 127');
        
        $item->validate();
    }

    public function testValidateUnitAmountRequired(): void
    {
        $item = new Item([
            'name' => 'Test Item',
            'quantity' => '1',
            'tax_rate' => '0.05'
            // unit_amount is not set but gets created as empty Money object
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must not be NULL');
        
        $item->validate();
    }

    public function testValidateQuantityRequired(): void
    {
        $item = new Item([
            'name' => 'Test Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'tax_rate' => '0.05'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity in Item must not be NULL');
        
        $item->validate();
    }

    public function testValidateTaxRateMaxLength(): void
    {
        $item = new Item([
            'name' => 'Test Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => str_repeat('1', 11) // 11 characters, max is 10
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tax_rate in Item must have maxlength of 10');
        
        $item->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCategoriesProvider')]
    public function testValidCategories(string $category): void
    {
        $item = new Item([
            'name' => 'Test Item',
            'unit_amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'quantity' => '1',
            'tax_rate' => '0.05',
            'category' => $category
        ]);
        
        $item->validate();
        $this->assertEquals($category, $item->category);
    }

    public static function validCategoriesProvider(): array
    {
        return [
            ['DIGITAL_GOODS'],
            ['PHYSICAL_GOODS'],
            ['DONATION'],
        ];
    }

    public function testUnitAmountIsInitialized(): void
    {
        $item = new Item();
        
        // unit_amount should be initialized automatically
        $this->assertInstanceOf(Money::class, $item->unit_amount);
    }

    public function testInitTaxMethod(): void
    {
        $item = new Item();
        
        $money = $item->initTax();
        
        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($money, $item->tax);
    }

    public function testExperienceContextPropertyExists(): void
    {
        $item = new Item();
        
        $this->assertTrue(property_exists($item, 'experience_context'));
        $this->assertNull($item->experience_context);
    }

    public function testComplexItemData(): void
    {
        $complexData = [
            'name' => 'Premium Digital Product',
            'unit_amount' => [
                'currency_code' => 'EUR',
                'value' => '299.99'
            ],
            'tax' => [
                'currency_code' => 'EUR',
                'value' => '57.00'
            ],
            'quantity' => '3',
            'tax_rate' => '0.19',
            'description' => 'Premium digital software license with extended support',
            'sku' => 'PREM-SOFT-2024-001',
            'category' => 'DIGITAL_GOODS'
        ];
        
        $item = new Item($complexData);
        
        $this->assertEquals('Premium Digital Product', $item->name);
        $this->assertEquals('DIGITAL_GOODS', $item->category);
        $this->assertEquals('3', $item->quantity);
        
        // Verify validation passes for complex valid data
        $item->validate();
        $this->assertTrue(true);
    }
}