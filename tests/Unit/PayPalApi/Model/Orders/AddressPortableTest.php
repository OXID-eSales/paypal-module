<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use PHPUnit\Framework\TestCase;

class AddressPortableTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'address_line_1' => '123 Main Street',
            'address_line_2' => 'Apt 4B',
            'admin_area_2' => 'San Jose',
            'admin_area_1' => 'CA',
            'postal_code' => '95131',
            'country_code' => 'US'
        ];
        
        $address = new AddressPortable($data);
        
        $this->assertEquals('123 Main Street', $address->address_line_1);
        $this->assertEquals('Apt 4B', $address->address_line_2);
        $this->assertEquals('San Jose', $address->admin_area_2);
        $this->assertEquals('CA', $address->admin_area_1);
        $this->assertEquals('95131', $address->postal_code);
        $this->assertEquals('US', $address->country_code);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'address_line_1' => '456 Oak Avenue',
            'country_code' => 'CA'
        ];
        
        $address = new AddressPortable($data);
        
        $this->assertEquals('456 Oak Avenue', $address->address_line_1);
        $this->assertEquals('CA', $address->country_code);
        $this->assertNull($address->address_line_2);
        $this->assertNull($address->admin_area_2);
        $this->assertNull($address->admin_area_1);
        $this->assertNull($address->postal_code);
    }

    public function testConstructorWithNoData(): void
    {
        $address = new AddressPortable();
        
        $this->assertNull($address->address_line_1);
        $this->assertNull($address->address_line_2);
        $this->assertNull($address->admin_area_2);
        $this->assertNull($address->admin_area_1);
        $this->assertNull($address->postal_code);
        $this->assertNull($address->country_code);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $address = new AddressPortable([
            'address_line_1' => '789 Pine Street',
            'admin_area_2' => 'New York',
            'country_code' => 'US'
        ]);
        
        $result = $address->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'address_line_1'));
        $this->assertTrue(property_exists($result, 'admin_area_2'));
        $this->assertTrue(property_exists($result, 'country_code'));
        $this->assertFalse(property_exists($result, 'address_line_2'));
        $this->assertFalse(property_exists($result, 'admin_area_1'));
        $this->assertFalse(property_exists($result, 'postal_code'));
    }

    public function testValidateWithValidData(): void
    {
        $address = new AddressPortable([
            'address_line_1' => '123 Valid Street',
            'admin_area_2' => 'Valid City',
            'admin_area_1' => 'CA',
            'postal_code' => '12345',
            'country_code' => 'US'
        ]);
        
        // Should not throw exception
        $address->validate();
        $this->assertTrue(true);
    }

    public function testValidateAddressLine1TooLong(): void
    {
        $address = new AddressPortable([
            'address_line_1' => str_repeat('a', 301), // 301 characters, max is 300
            'country_code' => 'US'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('address_line_1 in AddressPortable must have maxlength of 300');
        
        $address->validate();
    }

    public function testValidateCountryCodeTooLong(): void
    {
        $address = new AddressPortable([
            'address_line_1' => '123 Test Street',
            'country_code' => 'USA' // 3 characters, max is 2
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('country_code in AddressPortable must have maxlength of 2');
        
        $address->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCountryCodesProvider')]
    public function testValidCountryCodes(string $countryCode): void
    {
        $address = new AddressPortable([
            'address_line_1' => '123 Test Street',
            'country_code' => $countryCode
        ]);
        
        $address->validate();
        $this->assertEquals($countryCode, $address->country_code);
    }

    public static function validCountryCodesProvider(): array
    {
        return [
            ['US'],
            ['CA'],
            ['GB'],
            ['DE'],
            ['FR'],
            ['JP'],
            ['AU'],
        ];
    }

    public function testExperienceContextPropertyExists(): void
    {
        $address = new AddressPortable();
        
        $this->assertTrue(property_exists($address, 'experience_context'));
        $this->assertNull($address->experience_context);
    }
}