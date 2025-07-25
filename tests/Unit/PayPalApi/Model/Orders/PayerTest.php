<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Payer;
use PHPUnit\Framework\TestCase;

class PayerTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'email_address' => 'john.doe@example.com',
            'birth_date' => '1990-01-15'
        ];
        
        $payer = new Payer($data);
        
        $this->assertEquals('john.doe@example.com', $payer->email_address);
        $this->assertEquals('1990-01-15', $payer->birth_date);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'email_address' => 'jane@example.com'
        ];
        
        $payer = new Payer($data);
        
        $this->assertEquals('jane@example.com', $payer->email_address);
        $this->assertNull($payer->name);
        $this->assertNull($payer->phone);
        $this->assertNull($payer->birth_date);
        $this->assertNull($payer->tax_info);
    }

    public function testConstructorWithNoData(): void
    {
        $payer = new Payer();
        
        $this->assertNull($payer->email_address);
        $this->assertNull($payer->name);
        $this->assertNull($payer->phone);
        $this->assertNull($payer->birth_date);
        $this->assertNull($payer->address);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $payer = new Payer([
            'email_address' => 'test@example.com',
            'birth_date' => '1985-05-20'
        ]);
        
        $result = $payer->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'email_address'));
        $this->assertTrue(property_exists($result, 'birth_date'));
        $this->assertFalse(property_exists($result, 'name'));
        $this->assertFalse(property_exists($result, 'phone'));
        // $this->assertFalse(property_exists($result, 'tax_info'));
        $this->assertEquals('test@example.com', $result->email_address);
        $this->assertEquals('1985-05-20', $result->birth_date);
    }

    public function testValidateWithValidData(): void
    {
        $payer = new Payer([
            'email_address' => 'valid@example.com',
            'birth_date' => '1990-12-25'
        ]);
        
        // Should not throw exception
        $payer->validate();
        $this->assertTrue(true);
    }

    public function testValidateEmailAddressTooLong(): void
    {
        $payer = new Payer([
            'email_address' => str_repeat('a', 245) . '@example.com' // Too long email
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email_address in PayerBase must have maxlength of 254');
        
        $payer->validate();
    }

    public function testValidateBirthDateTooShort(): void
    {
        $payer = new Payer([
            'birth_date' => '1990-1-1' // 8 characters, min is 10
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('birth_date in Payer must have minlength of 10');
        
        $payer->validate();
    }

    public function testValidateBirthDateTooLong(): void
    {
        $payer = new Payer([
            'birth_date' => '1990-01-01-extra' // 15 characters, max is 10
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('birth_date in Payer must have maxlength of 10');
        
        $payer->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validEmailProvider')]
    public function testValidEmailAddresses(string $email): void
    {
        $payer = new Payer([
            'email_address' => $email
        ]);
        
        $payer->validate();
        $this->assertEquals($email, $payer->email_address);
    }

    public static function validEmailProvider(): array
    {
        return [
            ['user@example.com'],
            ['test.email@domain.org'],
            ['another+email@test.co.uk'],
            ['simple@test.io'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validBirthDateProvider')]
    public function testValidBirthDates(string $birthDate): void
    {
        $payer = new Payer([
            'birth_date' => $birthDate
        ]);
        
        $payer->validate();
        $this->assertEquals($birthDate, $payer->birth_date);
    }

    public static function validBirthDateProvider(): array
    {
        return [
            ['1990-01-01'],
            ['1985-12-31'],
            ['2000-06-15'],
            ['1975-03-22'],
        ];
    }

    public function testExperienceContextPropertyExists(): void
    {
        $payer = new Payer();
        
        $this->assertTrue(property_exists($payer, 'experience_context'));
        $this->assertNull($payer->experience_context);
    }

    public function testComplexPayerData(): void
    {
        $complexData = [
            'name' => [
                'prefix' => 'Mr.',
                'given_name' => 'John',
                'surname' => 'Smith',
                'middle_name' => 'William',
                'suffix' => 'Jr.'
            ],
            'email_address' => 'john.smith@example.com',
            'phone' => [
                'phone_type' => 'MOBILE',
                'phone_number' => [
                    'country_code' => '1',
                    'national_number' => '5551234567'
                ]
            ],
            'birth_date' => '1980-07-04',
            'address' => [
                'address_line_1' => '123 Main St',
                'admin_area_2' => 'Anytown',
                'admin_area_1' => 'CA',
                'postal_code' => '12345',
                'country_code' => 'US'
            ]
        ];
        
        $payer = new Payer($complexData);
        
        $this->assertEquals('john.smith@example.com', $payer->email_address);
        $this->assertEquals('1980-07-04', $payer->birth_date);
        
        // Verify validation passes for complex valid data
        $payer->validate();
        $this->assertTrue(true);
    }
}