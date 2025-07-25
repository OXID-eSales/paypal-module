<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'country_code' => '1',
            'national_number' => '5551234567',
            'extension_number' => '123'
        ];
        
        $phone = new Phone($data);
        
        $this->assertEquals('1', $phone->country_code);
        $this->assertEquals('5551234567', $phone->national_number);
        $this->assertEquals('123', $phone->extension_number);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'country_code' => '44',
            'national_number' => '7700900123'
        ];
        
        $phone = new Phone($data);
        
        $this->assertEquals('44', $phone->country_code);
        $this->assertEquals('7700900123', $phone->national_number);
        $this->assertNull($phone->extension_number);
    }

    public function testConstructorWithNoData(): void
    {
        $phone = new Phone();
        
        $this->assertNull($phone->country_code);
        $this->assertNull($phone->national_number);
        $this->assertNull($phone->extension_number);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $phone = new Phone([
            'country_code' => '49',
            'national_number' => '30123456789'
        ]);
        
        $result = $phone->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'country_code'));
        $this->assertTrue(property_exists($result, 'national_number'));
        $this->assertFalse(property_exists($result, 'extension_number'));
        $this->assertEquals('49', $result->country_code);
        $this->assertEquals('30123456789', $result->national_number);
    }

    public function testValidateWithValidData(): void
    {
        $phone = new Phone([
            'country_code' => '1',
            'national_number' => '5551234567'
        ]);
        
        // Should not throw exception
        $phone->validate();
        $this->assertTrue(true);
    }

    public function testValidateCountryCodeRequired(): void
    {
        $phone = new Phone([
            'national_number' => '5551234567'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('country_code in Phone must not be NULL');
        
        $phone->validate();
    }

    public function testValidateNationalNumberRequired(): void
    {
        $phone = new Phone([
            'country_code' => '1'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('national_number in Phone must not be NULL');
        
        $phone->validate();
    }

    public function testValidateCountryCodeTooLong(): void
    {
        $phone = new Phone([
            'country_code' => '1234', // 4 characters, max is 3
            'national_number' => '5551234567'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('country_code in Phone must have maxlength of 3');
        
        $phone->validate();
    }

    public function testValidateNationalNumberTooLong(): void
    {
        $phone = new Phone([
            'country_code' => '1',
            'national_number' => str_repeat('1', 15) // 15 characters, max is 14
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('national_number in Phone must have maxlength of 14');
        
        $phone->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCountryCodesProvider')]
    public function testValidCountryCodes(string $countryCode): void
    {
        $phone = new Phone([
            'country_code' => $countryCode,
            'national_number' => '123456789'
        ]);
        
        $phone->validate();
        $this->assertEquals($countryCode, $phone->country_code);
    }

    public static function validCountryCodesProvider(): array
    {
        return [
            ['1'],    // US/Canada
            ['44'],   // UK
            ['49'],   // Germany
            ['33'],   // France
            ['81'],   // Japan
            ['86'],   // China
        ];
    }

    public function testExperienceContextPropertyExists(): void
    {
        $phone = new Phone();
        
        $this->assertTrue(property_exists($phone, 'experience_context'));
        $this->assertNull($phone->experience_context);
    }
}