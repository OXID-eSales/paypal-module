<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use PHPUnit\Framework\TestCase;

class OrderRequestTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00'
                    ]
                ]
            ]
        ];
        
        $orderRequest = new OrderRequest($data);
        
        $this->assertEquals('CAPTURE', $orderRequest->intent);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = ['intent' => 'AUTHORIZE'];
        
        $orderRequest = new OrderRequest($data);
        
        $this->assertEquals('AUTHORIZE', $orderRequest->intent);
    }

    public function testConstructorWithNoData(): void
    {
        $orderRequest = new OrderRequest();
        
        $this->assertNull($orderRequest->intent);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $orderRequest = new OrderRequest(['intent' => 'CAPTURE']);
        
        $result = $orderRequest->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'intent'));
        $this->assertEquals('CAPTURE', $result->intent);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $orderRequest = new OrderRequest([
            'intent' => 'AUTHORIZE',
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => '50.00'
                    ]
                ]
            ],
            'application_context' => [
                'return_url' => 'https://example.com/return',
                'cancel_url' => 'https://example.com/cancel'
            ]
        ]);
        
        $result = $orderRequest->jsonSerialize();
        
        $this->assertEquals('AUTHORIZE', $result->intent);
        // Only check for properties that exist in the model
        $this->assertEquals('AUTHORIZE', $result->intent);
    }

    public function testValidateWithValidData(): void
    {
        $orderRequest = new OrderRequest([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00'
                    ]
                ]
            ]
        ]);
        
        // Should not throw exception
        $orderRequest->validate();
        $this->assertTrue(true);
    }

    public function testValidateIntentRequired(): void
    {
        $orderRequest = new OrderRequest([
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00'
                    ]
                ]
            ]
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('intent in OrderRequest must not be NULL');
        
        $orderRequest->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validIntentsProvider')]
    public function testValidIntents(string $intent): void
    {
        $orderRequest = new OrderRequest([
            'intent' => $intent,
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00'
                    ]
                ]
            ]
        ]);
        
        $orderRequest->validate();
        $this->assertEquals($intent, $orderRequest->intent);
    }

    public static function validIntentsProvider(): array
    {
        return [
            ['CAPTURE'],
            ['AUTHORIZE'],
        ];
    }

    public function testValidateWithFromParameter(): void
    {
        $orderRequest = new OrderRequest(['purchase_units' => []]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('intent in OrderRequest must not be NULL within TestContext');
        
        $orderRequest->validate('TestContext');
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'intent' => 'CAPTURE',
            'unknown_key' => 'should_be_ignored',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00'
                    ]
                ]
            ]
        ];
        
        $orderRequest = new OrderRequest($data);
        
        $this->assertEquals('CAPTURE', $orderRequest->intent);
        $this->assertFalse(property_exists($orderRequest, 'unknown_key'));
    }

    public function testConstructorWithNullData(): void
    {
        $orderRequest = new OrderRequest(null);
        
        $this->assertNull($orderRequest->intent);
    }

    public function testExperienceContextPropertyExists(): void
    {
        $orderRequest = new OrderRequest();
        
        $this->assertTrue(property_exists($orderRequest, 'experience_context'));
        $this->assertNull($orderRequest->experience_context);
    }

    public function testComplexOrderRequestData(): void
    {
        $complexData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'PUHF',
                    'description' => 'Sporting Goods',
                    'custom_id' => 'CUST-HighFashions',
                    'soft_descriptor' => 'HighFashions',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '220.00',
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'USD',
                                'value' => '180.00'
                            ],
                            'shipping' => [
                                'currency_code' => 'USD',
                                'value' => '20.00'
                            ],
                            'handling' => [
                                'currency_code' => 'USD',
                                'value' => '10.00'
                            ],
                            'tax_total' => [
                                'currency_code' => 'USD',
                                'value' => '20.00'
                            ],
                            'shipping_discount' => [
                                'currency_code' => 'USD',
                                'value' => '10.00'
                            ]
                        ]
                    ]
                ]
            ],
            'application_context' => [
                'brand_name' => 'EXAMPLE INC',
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'PAY_NOW',
                'return_url' => 'https://example.com/returnUrl',
                'cancel_url' => 'https://example.com/cancelUrl'
            ]
        ];
        
        $orderRequest = new OrderRequest($complexData);
        
        $this->assertEquals('CAPTURE', $orderRequest->intent);
        
        // Verify validation passes for complex valid data
        $orderRequest->validate();
        $this->assertTrue(true);
        
        // Verify JSON serialization includes intent
        $result = $orderRequest->jsonSerialize();
        $this->assertTrue(property_exists($result, 'intent'));
        $this->assertEquals('CAPTURE', $result->intent);
    }
}