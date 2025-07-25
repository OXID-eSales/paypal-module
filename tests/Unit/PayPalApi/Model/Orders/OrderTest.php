<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'id' => 'ORDER_123',
            'status' => 'CREATED',
            'intent' => 'CAPTURE',
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ];
        
        $order = new Order($data);
        
        $this->assertEquals('ORDER_123', $order->id);
        $this->assertEquals('CREATED', $order->status);
        $this->assertEquals('CAPTURE', $order->intent);
        $this->assertEquals('2023-01-01T12:00:00Z', $order->create_time);
        $this->assertEquals('2023-01-01T13:00:00Z', $order->update_time);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = [
            'id' => 'ORDER_456',
            'status' => 'APPROVED'
        ];
        
        $order = new Order($data);
        
        $this->assertEquals('ORDER_456', $order->id);
        $this->assertEquals('APPROVED', $order->status);
        $this->assertNull($order->intent);
        $this->assertNull($order->create_time);
        $this->assertNull($order->update_time);
    }

    public function testConstructorWithNoData(): void
    {
        $order = new Order();
        
        $this->assertNull($order->id);
        $this->assertNull($order->status);
        $this->assertNull($order->intent);
        $this->assertNull($order->create_time);
        $this->assertNull($order->update_time);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $order = new Order([
            'id' => 'ORDER_789',
            'status' => 'COMPLETED'
        ]);
        
        $result = $order->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'id'));
        $this->assertTrue(property_exists($result, 'status'));
        $this->assertFalse(property_exists($result, 'intent'));
        $this->assertFalse(property_exists($result, 'create_time'));
        $this->assertFalse(property_exists($result, 'update_time'));
        $this->assertEquals('ORDER_789', $result->id);
        $this->assertEquals('COMPLETED', $result->status);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $order = new Order([
            'id' => 'ORDER_FULL',
            'status' => 'APPROVED',
            'intent' => 'AUTHORIZE',
            'create_time' => '2023-01-01T10:00:00Z',
            'update_time' => '2023-01-01T11:00:00Z'
        ]);
        
        $result = $order->jsonSerialize();
        
        $this->assertEquals('ORDER_FULL', $result->id);
        $this->assertEquals('APPROVED', $result->status);
        $this->assertEquals('AUTHORIZE', $result->intent);
        $this->assertEquals('2023-01-01T10:00:00Z', $result->create_time);
        $this->assertEquals('2023-01-01T11:00:00Z', $result->update_time);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statusProvider')]
    public function testValidOrderStatuses(string $status): void
    {
        $order = new Order([
            'id' => 'ORDER_STATUS_TEST',
            'status' => $status
        ]);
        
        $this->assertEquals($status, $order->status);
    }

    public static function statusProvider(): array
    {
        return [
            ['CREATED'],
            ['SAVED'],
            ['APPROVED'],
            ['VOIDED'],
            ['COMPLETED'],
            ['PAYER_ACTION_REQUIRED'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('intentProvider')]
    public function testValidOrderIntents(string $intent): void
    {
        $order = new Order([
            'id' => 'ORDER_INTENT_TEST',
            'intent' => $intent
        ]);
        
        $this->assertEquals($intent, $order->intent);
    }

    public static function intentProvider(): array
    {
        return [
            ['CAPTURE'],
            ['AUTHORIZE'],
        ];
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'id' => 'ORDER_UNKNOWN',
            'status' => 'CREATED',
            'unknown_field' => 'should_be_ignored',
            'another_unknown' => 'also_ignored'
        ];
        
        $order = new Order($data);
        
        $this->assertEquals('ORDER_UNKNOWN', $order->id);
        $this->assertEquals('CREATED', $order->status);
        $this->assertFalse(property_exists($order, 'unknown_field'));
        $this->assertFalse(property_exists($order, 'another_unknown'));
    }

    public function testConstructorWithNullData(): void
    {
        $order = new Order(null);
        
        $this->assertNull($order->id);
        $this->assertNull($order->status);
        $this->assertNull($order->intent);
    }

    public function testExperienceContextPropertyExists(): void
    {
        $order = new Order();
        
        $this->assertTrue(property_exists($order, 'experience_context'));
        $this->assertNull($order->experience_context);
    }

    public function testComplexOrderData(): void
    {
        $complexData = [
            'id' => 'ORDER_COMPLEX',
            'status' => 'APPROVED',
            'intent' => 'CAPTURE',
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ];
        
        $order = new Order($complexData);
        
        $this->assertEquals('ORDER_COMPLEX', $order->id);
        $this->assertEquals('APPROVED', $order->status);
        $this->assertEquals('CAPTURE', $order->intent);
        
        // Verify that data is handled correctly
        $result = $order->jsonSerialize();
        $this->assertTrue(property_exists($result, 'id'));
        $this->assertTrue(property_exists($result, 'status'));
        $this->assertTrue(property_exists($result, 'intent'));
    }
}