<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Money;
use PHPUnit\Framework\TestCase;

class MoneyOrdersTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'currency_code' => 'USD',
            'value' => '100.00'
        ];
        
        $money = new Money($data);
        
        $this->assertEquals('USD', $money->currency_code);
        $this->assertEquals('100.00', $money->value);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'currency_code' => 'EUR',
            'value' => '50.00'
        ];
        
        $money = new Money($data);
        
        $this->assertEquals('EUR', $money->currency_code);
        $this->assertEquals('50.00', $money->value);
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
        $money = new Money([
            'currency_code' => 'USD',
            'value' => '100.00'
        ]);
        
        $result = $money->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'currency_code'));
        $this->assertTrue(property_exists($result, 'value'));
        $this->assertEquals('USD', $result->currency_code);
        $this->assertEquals('100.00', $result->value);
    }

    public function testJsonSerializeWithNullValues(): void
    {
        $money = new Money([
            'currency_code' => 'CAD'
            // value is null
        ]);
        
        $result = $money->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'currency_code'));
        $this->assertFalse(property_exists($result, 'value'));
        $this->assertEquals('CAD', $result->currency_code);
    }

    public function testValidateWithValidData(): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => '100.00'
        ]);
        
        // Should not throw exception
        $money->validate();
        $this->assertTrue(true);
    }

    public function testValidateWithMissingCurrencyCode(): void
    {
        $money = new Money([
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must not be NULL');
        
        $money->validate();
    }

    public function testValidateWithMissingValue(): void
    {
        $money = new Money([
            'currency_code' => 'USD'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value in Money must not be NULL');
        
        $money->validate();
    }

    public function testValidateCurrencyCodeTooShort(): void
    {
        $money = new Money([
            'currency_code' => 'US', // 2 characters, min is 3
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must have minlength of 3');
        
        $money->validate();
    }

    public function testValidateCurrencyCodeTooLong(): void
    {
        $money = new Money([
            'currency_code' => 'USDD', // 4 characters, max is 3
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must have maxlength of 3');
        
        $money->validate();
    }

    public function testValidateValueTooLong(): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => str_repeat('1', 33) // 33 characters, max is 32
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value in Money must have maxlength of 32');
        
        $money->validate();
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
            ['0.00'],
            ['1.00'],
            ['100.00'],
            ['999.99'],
            ['1000'],
            ['1000.50'],
            ['12345.67'],
            ['0.01'],
            ['999999999.99'],
        ];
    }

    public function testValidateWithFromParameter(): void
    {
        $money = new Money([
            'currency_code' => 'US', // Too short
            'value' => '100.00'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currency_code in Money must have minlength of 3 within TestContext');
        
        $money->validate('TestContext');
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $money = new Money([
            'currency_code' => 'USD',
            'value' => '100.00',
            'unknown_field' => 'should_be_ignored',
            'another_unknown' => 123
        ]);
        
        $this->assertEquals('USD', $money->currency_code);
        $this->assertEquals('100.00', $money->value);
        $this->assertFalse(property_exists($money, 'unknown_field'));
        $this->assertFalse(property_exists($money, 'another_unknown'));
    }

    public function testExperienceContextPropertyExists(): void
    {
        $money = new Money();
        
        $this->assertTrue(property_exists($money, 'experience_context'));
        $this->assertNull($money->experience_context);
    }

    public function testComplexMoneyData(): void
    {
        $complexData = [
            'currency_code' => 'EUR',
            'value' => '1234567890.12'
        ];
        
        $money = new Money($complexData);
        
        $this->assertEquals('EUR', $money->currency_code);
        $this->assertEquals('1234567890.12', $money->value);
        
        // Verify validation passes for complex valid data
        $money->validate();
        $this->assertTrue(true);
    }
}