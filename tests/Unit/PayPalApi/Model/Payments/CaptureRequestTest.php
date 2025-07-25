<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Payments;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\CaptureRequest;
use PHPUnit\Framework\TestCase;

class CaptureRequestTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'invoice_id' => 'INV-123',
            'final_capture' => true,
            'note_to_payer' => 'Thank you for your payment',
            'soft_descriptor' => 'PAYPAL TEST'
        ];
        
        $captureRequest = new CaptureRequest($data);
        
        $this->assertEquals('INV-123', $captureRequest->invoice_id);
        $this->assertTrue($captureRequest->final_capture);
        $this->assertEquals('Thank you for your payment', $captureRequest->note_to_payer);
        $this->assertEquals('PAYPAL TEST', $captureRequest->soft_descriptor);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '50.00'
            ]
        ];
        
        $captureRequest = new CaptureRequest($data);
        
        $this->assertNull($captureRequest->invoice_id);
        $this->assertEquals('false', $captureRequest->final_capture); // default value
        $this->assertNull($captureRequest->note_to_payer);
        $this->assertNull($captureRequest->soft_descriptor);
    }

    public function testConstructorWithNoData(): void
    {
        $captureRequest = new CaptureRequest();
        
        $this->assertNull($captureRequest->amount);
        $this->assertNull($captureRequest->invoice_id);
        $this->assertEquals('false', $captureRequest->final_capture); // default value
        $this->assertNull($captureRequest->payment_instruction);
        $this->assertNull($captureRequest->note_to_payer);
        $this->assertNull($captureRequest->soft_descriptor);
        $this->assertNull($captureRequest->supplementary_data);
    }

    public function testConstructorWithNullData(): void
    {
        $captureRequest = new CaptureRequest(null);
        
        $this->assertNull($captureRequest->amount);
        $this->assertNull($captureRequest->invoice_id);
        $this->assertEquals('false', $captureRequest->final_capture); // default value
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $captureRequest = new CaptureRequest([
            'invoice_id' => 'INV-456',
            'final_capture' => false
        ]);
        
        $result = $captureRequest->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'invoice_id'));
        $this->assertTrue(property_exists($result, 'final_capture'));
        $this->assertFalse(property_exists($result, 'amount'));
        $this->assertFalse(property_exists($result, 'note_to_payer'));
        $this->assertEquals('INV-456', $result->invoice_id);
        $this->assertFalse($result->final_capture);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $captureRequest = new CaptureRequest([
            'amount' => [
                'currency_code' => 'GBP',
                'value' => '75.50'
            ],
            'invoice_id' => 'INV-FULL',
            'final_capture' => true,
            'note_to_payer' => 'Complete payment',
            'soft_descriptor' => 'TEST CAPTURE'
        ]);
        
        $result = $captureRequest->jsonSerialize();
        
        $this->assertEquals('INV-FULL', $result->invoice_id);
        $this->assertTrue($result->final_capture);
        $this->assertEquals('Complete payment', $result->note_to_payer);
        $this->assertEquals('TEST CAPTURE', $result->soft_descriptor);
    }

    public function testValidateWithValidData(): void
    {
        $captureRequest = new CaptureRequest([
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ],
            'invoice_id' => 'INV-VALID'
        ]);
        
        // Should not throw exception
        $captureRequest->validate();
        $this->assertTrue(true);
    }

    public function testValidateInvoiceIdTooShort(): void
    {
        $captureRequest = new CaptureRequest([
            'invoice_id' => '', // empty string, min is 1
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ]
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoice_id in CaptureRequest must have minlength of 1');
        
        $captureRequest->validate();
    }

    public function testValidateInvoiceIdTooLong(): void
    {
        $captureRequest = new CaptureRequest([
            'invoice_id' => str_repeat('a', 128), // 128 characters, max is 127
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ]
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoice_id in CaptureRequest must have maxlength of 127');
        
        $captureRequest->validate();
    }

    public function testValidateNoteToPayerTooLong(): void
    {
        $captureRequest = new CaptureRequest([
            'note_to_payer' => str_repeat('a', 256), // 256 characters, max is 255
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ]
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('note_to_payer in CaptureRequest must have maxlength of 255');
        
        $captureRequest->validate();
    }

    public function testValidateSoftDescriptorTooLong(): void
    {
        $captureRequest = new CaptureRequest([
            'soft_descriptor' => str_repeat('a', 23), // 23 characters, max is 22
            'amount' => [
                'currency_code' => 'USD',
                'value' => '100.00'
            ]
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('soft_descriptor in CaptureRequest must have maxlength of 22');
        
        $captureRequest->validate();
    }

    public function testValidateWithFromParameter(): void
    {
        $captureRequest = new CaptureRequest([
            'invoice_id' => str_repeat('a', 128) // Too long
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoice_id in CaptureRequest must have maxlength of 127 within TestContext');
        
        $captureRequest->validate('TestContext');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('finalCaptureProvider')]
    public function testFinalCaptureValues($finalCapture, $expected): void
    {
        $captureRequest = new CaptureRequest([
            'final_capture' => $finalCapture
        ]);
        
        $this->assertEquals($expected, $captureRequest->final_capture);
    }

    public static function finalCaptureProvider(): array
    {
        return [
            [true, true],
            [false, false],
            ['true', 'true'],
            ['false', 'false'],
        ];
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'invoice_id' => 'INV-UNKNOWN',
            'final_capture' => true,
            'unknown_field' => 'should_be_ignored',
            'another_unknown' => 'also_ignored'
        ];
        
        $captureRequest = new CaptureRequest($data);
        
        $this->assertEquals('INV-UNKNOWN', $captureRequest->invoice_id);
        $this->assertTrue($captureRequest->final_capture);
        $this->assertFalse(property_exists($captureRequest, 'unknown_field'));
        $this->assertFalse(property_exists($captureRequest, 'another_unknown'));
    }

    public function testExperienceContextPropertyExists(): void
    {
        $captureRequest = new CaptureRequest();
        
        $this->assertTrue(property_exists($captureRequest, 'experience_context'));
        $this->assertNull($captureRequest->experience_context);
    }

    public function testInitAmountMethod(): void
    {
        $captureRequest = new CaptureRequest();
        
        $money = $captureRequest->initAmount();
        
        $this->assertInstanceOf('OxidSolutionCatalysts\\PayPalApi\\Model\\Payments\\Money', $money);
        $this->assertSame($money, $captureRequest->amount);
    }

    public function testComplexCaptureRequestData(): void
    {
        $complexData = [
            'amount' => [
                'currency_code' => 'USD',
                'value' => '150.75'
            ],
            'invoice_id' => 'INV-COMPLEX-123',
            'final_capture' => true,
            'note_to_payer' => 'Final payment for order #12345',
            'soft_descriptor' => 'MERCHANT PAYMENT',
            'payment_instruction' => [
                'platform_fees' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '5.00'
                        ]
                    ]
                ]
            ]
        ];
        
        $captureRequest = new CaptureRequest($complexData);
        
        $this->assertEquals('INV-COMPLEX-123', $captureRequest->invoice_id);
        $this->assertTrue($captureRequest->final_capture);
        $this->assertEquals('Final payment for order #12345', $captureRequest->note_to_payer);
        $this->assertEquals('MERCHANT PAYMENT', $captureRequest->soft_descriptor);
        
        // Verify validation passes for complex valid data
        $captureRequest->validate();
        $this->assertTrue(true);
    }
}