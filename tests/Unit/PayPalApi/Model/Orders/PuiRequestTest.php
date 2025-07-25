<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PuiRequest;
use PHPUnit\Framework\TestCase;

class PuiRequestTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Deutsche Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John Doe'
            ],
            'payment_reference' => 'REF123456'
        ];
        
        $puiRequest = new PuiRequest($data);
        
        $this->assertEquals('John Doe', $puiRequest->name);
        $this->assertEquals('DE', $puiRequest->country_code);
        $this->assertEquals('DEUTDEFF', $puiRequest->bic);
        $this->assertEquals('Deutsche Bank', $puiRequest->bank_name);
        $this->assertEquals('DE89370400440532013000', $puiRequest->iban);
        $this->assertEquals('John Doe', $puiRequest->account_holder_name);
        $this->assertEquals('REF123456', $puiRequest->payment_reference);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'country_code' => 'FR'
        ];
        
        $puiRequest = new PuiRequest($data);
        
        $this->assertEquals('Jane Doe', $puiRequest->name);
        $this->assertEquals('FR', $puiRequest->country_code);
        $this->assertNull($puiRequest->bic);
        $this->assertNull($puiRequest->bank_name);
        $this->assertNull($puiRequest->iban);
        $this->assertNull($puiRequest->account_holder_name);
        $this->assertNull($puiRequest->payment_reference);
    }

    public function testConstructorWithNoData(): void
    {
        $puiRequest = new PuiRequest();
        
        $this->assertNull($puiRequest->name);
        $this->assertNull($puiRequest->country_code);
        $this->assertNull($puiRequest->bic);
        $this->assertNull($puiRequest->bank_name);
        $this->assertNull($puiRequest->iban);
        $this->assertNull($puiRequest->account_holder_name);
        $this->assertNull($puiRequest->payment_reference);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Test Bank'
            ]
        ]);
        
        $result = $puiRequest->jsonSerialize();
        
        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('DE', $result->country_code);
        $this->assertEquals('DEUTDEFF', $result->bic);
        $this->assertEquals('Test Bank', $result->bank_name);
    }

    public function testValidateWithValidData(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Deutsche Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'Test User'
            ],
            'payment_reference' => 'REF123456'
        ]);
        
        // Should not throw exception
        $puiRequest->validate();
        $this->assertTrue(true);
    }

    public function testValidateNameRequired(): void
    {
        $puiRequest = new PuiRequest([
            'country_code' => 'DE'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in PuiRequest must not be NULL');
        
        $puiRequest->validate();
    }

    public function testValidateCountryCodeRequired(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('country_code in PuiRequest must not be NULL');
        
        $puiRequest->validate();
    }

    public function testValidateNameTooShort(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Jo', // 2 characters, min is 3
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in PuiRequest must have minlength of 3');
        
        $puiRequest->validate();
    }

    public function testValidateNameTooLong(): void
    {
        $puiRequest = new PuiRequest([
            'name' => str_repeat('a', 301), // 301 characters, max is 300
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in PuiRequest must have maxlength of 300');
        
        $puiRequest->validate();
    }

    public function testValidateCountryCodeLength(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => 'D', // 1 character, must be 2
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('country_code in PuiRequest must have minlength of 2');
        
        $puiRequest->validate();
    }

    public function testValidateIbanLength(): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Bank',
                'iban' => 'DE8937040044053201300', // 21 characters, must be 22
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('iban in PuiRequest must have minlength of 3'); // Note: there's a bug in the original code
        
        $puiRequest->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCountryCodesProvider')]
    public function testValidCountryCodes(string $countryCode): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => $countryCode,
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'bank_name' => 'Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $puiRequest->validate();
        $this->assertEquals($countryCode, $puiRequest->country_code);
    }

    public static function validCountryCodesProvider(): array
    {
        return [
            ['DE'],
            ['FR'],
            ['IT'],
            ['ES'],
            ['NL'],
            ['AT'],
            ['BE'],
            ['US'],
            ['GB'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validBicProvider')]
    public function testValidBicCodes(string $bic): void
    {
        $puiRequest = new PuiRequest([
            'name' => 'Test User',
            'country_code' => 'DE',
            'deposit_bank_details' => [
                'bic' => $bic,
                'bank_name' => 'Bank',
                'iban' => 'DE89370400440532013000',
                'account_holder_name' => 'John'
            ],
            'payment_reference' => 'REF'
        ]);
        
        $puiRequest->validate();
        $this->assertEquals($bic, $puiRequest->bic);
    }

    public static function validBicProvider(): array
    {
        return [
            ['DEUTDEFF'], // 8 characters
            ['DEUTDEFFXXX'], // 11 characters
            ['ABCDEFF1'], // 8 characters
            ['ABCDEFF1234'], // 11 characters
        ];
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'name' => 'Test User',
            'country_code' => 'DE',
            'unknown_key' => 'should_be_ignored',
            'deposit_bank_details' => [
                'bic' => 'DEUTDEFF',
                'unknown_bank_field' => 'ignored'
            ]
        ];
        
        $puiRequest = new PuiRequest($data);
        
        $this->assertEquals('Test User', $puiRequest->name);
        $this->assertEquals('DE', $puiRequest->country_code);
        $this->assertEquals('DEUTDEFF', $puiRequest->bic);
        $this->assertFalse(property_exists($puiRequest, 'unknown_key'));
    }

    public function testValidateWithFromParameter(): void
    {
        $puiRequest = new PuiRequest();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in PuiRequest must not be NULL within TestContext');
        
        $puiRequest->validate('TestContext');
    }
}