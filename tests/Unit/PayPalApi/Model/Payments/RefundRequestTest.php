<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Payments;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\RefundRequest;
use PHPUnit\Framework\TestCase;

class RefundRequestTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'amount' => [
                'currency_code' => 'USD',
                'value' => '50.00'
            ],
            'invoice_id' => 'REFUND-INV-123',
            'note_to_payer' => 'Refund for your order'
        ];
        
        $refundRequest = new RefundRequest($data);
        
        $this->assertEquals('REFUND-INV-123', $refundRequest->invoice_id);
        $this->assertEquals('Refund for your order', $refundRequest->note_to_payer);
    }

    public function testConstructorWithNoData(): void
    {
        $refundRequest = new RefundRequest();
        
        $this->assertNull($refundRequest->amount);
        $this->assertNull($refundRequest->invoice_id);
        $this->assertNull($refundRequest->note_to_payer);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $refundRequest = new RefundRequest([
            'invoice_id' => 'REFUND-456'
        ]);
        
        $result = $refundRequest->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'invoice_id'));
        $this->assertFalse(property_exists($result, 'amount'));
        $this->assertFalse(property_exists($result, 'note_to_payer'));
        $this->assertEquals('REFUND-456', $result->invoice_id);
    }

    public function testValidateWithValidData(): void
    {
        $refundRequest = new RefundRequest([
            'amount' => [
                'currency_code' => 'EUR',
                'value' => '25.00'
            ],
            'invoice_id' => 'VALID-REFUND'
        ]);
        
        // Should not throw exception
        $refundRequest->validate();
        $this->assertTrue(true);
    }

    public function testValidateInvoiceIdTooLong(): void
    {
        $refundRequest = new RefundRequest([
            'invoice_id' => str_repeat('a', 128) // 128 characters, max is 127
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invoice_id in RefundRequest must have maxlength of 127');
        
        $refundRequest->validate();
    }

    public function testValidateNoteToPayerTooLong(): void
    {
        $refundRequest = new RefundRequest([
            'note_to_payer' => str_repeat('a', 256) // 256 characters, max is 255
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('note_to_payer in RefundRequest must have maxlength of 255');
        
        $refundRequest->validate();
    }

    public function testExperienceContextPropertyExists(): void
    {
        $refundRequest = new RefundRequest();
        
        $this->assertTrue(property_exists($refundRequest, 'experience_context'));
        $this->assertNull($refundRequest->experience_context);
    }
}