<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'currency_code' => 'USD',
            'value' => '100.50'
        ];
        
        $money = new Money($data);
        
        $this->assertEquals('USD', $money->currency_code);
        $this->assertEquals('100.50', $money->value);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = ['currency_code' => 'EUR'];
        
        $money = new Money($data);
        
        $this->assertEquals('EUR', $money->currency_code);
        $this->assertNull($money->value);
    }

    public function testConstructorWithNoData(): void
    {
        $money = new Money();
        
        $this->assertNull($money->currency_code);
        $this->assertNull($money->value);
    }

    public function testConstructorWithNullData(): void
    {
        $money = new Money(null);
        
        $this->assertNull($money->currency_code);
        $this->assertNull($money->value);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $money = new Money(['currency_code' => 'USD']);
        $result = $money->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'currency_code'));
        $this->assertFalse(property_exists($result, 'value'));
        $this->assertEquals('USD', $result->currency_code);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $money = new Money([
            'currency_code' => 'EUR',
            'value' => '99.99'
        ]);
        $result = $money->jsonSerialize();
        
        $this->assertEquals('EUR', $result->currency_code);
        $this->assertEquals('99.99', $result->value);
    }

    public function testValidateWithValidData(): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => '100.00'
        ]);
        
        // Should not throw exception
        $money->validate();
        $this->assertTrue(true); // Assert test passes if no exception
    }

    public function testValidateWithMissingCurrencyCode(): void
    {
        $money = new Money(['value' => '100.00']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must not be NULL');
        
        $money->validate();
    }

    public function testValidateWithMissingValue(): void
    {
        $money = new Money(['currency_code' => 'USD']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value in Money must not be NULL');
        
        $money->validate();
    }

    public function testValidateWithInvalidCurrencyCodeLength(): void
    {
        $money = new Money([
            'currency_code' => 'US', // Too short
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must have minlength of 3');
        
        $money->validate();
    }

    public function testValidateWithCurrencyCodeTooLong(): void
    {
        $money = new Money([
            'currency_code' => 'USDD', // Too long
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must have maxlength of 3');
        
        $money->validate();
    }

    public function testValidateWithValueTooLong(): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => str_repeat('1', 33) // 33 characters, max is 32
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value in Money must have maxlength of 32');
        
        $money->validate();
    }

    public function testValidateWithFromParameter(): void
    {
        $money = new Money(['value' => '100.00']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must not be NULL within TestContext');
        
        $money->validate('TestContext');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCurrencyCodesProvider')]
    public function testValidCurrencyCodes(string $currencyCode): void
    {
        $money = new Money([
            'currency_code' => $currencyCode,
            'value' => '100.00'
        ]);
        
        $money->validate();
        $this->assertEquals($currencyCode, $money->currency_code);
    }

    public static function validCurrencyCodesProvider(): array
    {
        return [
            ['USD'],
            ['EUR'],
            ['GBP'],
            ['JPY'],
            ['CAD'],
            ['AUD'],
            ['CHF'],
            ['CNY'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validValuesProvider')]
    public function testValidValues(string $value): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => $value
        ]);
        
        $money->validate();
        $this->assertEquals($value, $money->value);
    }

    public static function validValuesProvider(): array
    {
        return [
            ['0'],
            ['0.00'],
            ['100'],
            ['100.50'],
            ['1000.99'],
            ['9999999999.99'],
            [str_repeat('9', 29) . '.99'], // Max length test
        ];
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'currency_code' => 'USD',
            'value' => '100.00',
            'unknown_key' => 'should_be_ignored'
        ];
        
        $money = new Money($data);
        
        $this->assertEquals('USD', $money->currency_code);
        $this->assertEquals('100.00', $money->value);
        $this->assertFalse(property_exists($money, 'unknown_key'));
    }
}