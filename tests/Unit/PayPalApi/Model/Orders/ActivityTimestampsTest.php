<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Orders;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ActivityTimestamps;
use PHPUnit\Framework\TestCase;

class ActivityTimestampsTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ];
        
        $timestamps = new ActivityTimestamps($data);
        
        $this->assertEquals('2023-01-01T12:00:00Z', $timestamps->create_time);
        $this->assertEquals('2023-01-01T13:00:00Z', $timestamps->update_time);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = ['create_time' => '2023-01-01T12:00:00Z'];
        
        $timestamps = new ActivityTimestamps($data);
        
        $this->assertEquals('2023-01-01T12:00:00Z', $timestamps->create_time);
        $this->assertNull($timestamps->update_time);
    }

    public function testConstructorWithNoData(): void
    {
        $timestamps = new ActivityTimestamps();
        
        $this->assertNull($timestamps->create_time);
        $this->assertNull($timestamps->update_time);
    }

    public function testConstructorWithNullData(): void
    {
        $timestamps = new ActivityTimestamps(null);
        
        $this->assertNull($timestamps->create_time);
        $this->assertNull($timestamps->update_time);
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $timestamps = new ActivityTimestamps(['create_time' => '2023-01-01T12:00:00Z']);
        $result = $timestamps->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'create_time'));
        $this->assertFalse(property_exists($result, 'update_time'));
        $this->assertEquals('2023-01-01T12:00:00Z', $result->create_time);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ]);
        $result = $timestamps->jsonSerialize();
        
        $this->assertEquals('2023-01-01T12:00:00Z', $result->create_time);
        $this->assertEquals('2023-01-01T13:00:00Z', $result->update_time);
    }

    public function testValidateWithValidData(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ]);
        
        // Should not throw exception
        $timestamps->validate();
        $this->assertTrue(true);
    }

    public function testValidateWithNullValuesDoesNotThrow(): void
    {
        $timestamps = new ActivityTimestamps();
        
        // Should not throw exception - null values are optional
        $timestamps->validate();
        $this->assertTrue(true);
    }

    public function testValidateCreateTimeTooShort(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => '2023-01-01T12:00:0' // 19 characters, min is 20
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('create_time in ActivityTimestamps must have minlength of 20');
        
        $timestamps->validate();
    }

    public function testValidateCreateTimeTooLong(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => str_repeat('a', 65) // 65 characters, max is 64
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('create_time in ActivityTimestamps must have maxlength of 64');
        
        $timestamps->validate();
    }

    public function testValidateUpdateTimeTooShort(): void
    {
        $timestamps = new ActivityTimestamps([
            'update_time' => '2023-01-01T12:00:0' // 19 characters, min is 20
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('update_time in ActivityTimestamps must have minlength of 20');
        
        $timestamps->validate();
    }

    public function testValidateUpdateTimeTooLong(): void
    {
        $timestamps = new ActivityTimestamps([
            'update_time' => str_repeat('b', 65) // 65 characters, max is 64
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('update_time in ActivityTimestamps must have maxlength of 64');
        
        $timestamps->validate();
    }

    public function testValidateWithFromParameter(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => 'short'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('create_time in ActivityTimestamps must have minlength of 20 within TestContext');
        
        $timestamps->validate('TestContext');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validTimestampsProvider')]
    public function testValidTimestamps(string $timestamp): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => $timestamp,
            'update_time' => $timestamp
        ]);
        
        $timestamps->validate();
        $this->assertEquals($timestamp, $timestamps->create_time);
        $this->assertEquals($timestamp, $timestamps->update_time);
    }

    public static function validTimestampsProvider(): array
    {
        return [
            ['2023-01-01T12:00:00Z'], // Exactly 20 characters
            ['2023-01-01T12:00:00.123Z'], // With milliseconds
            ['2023-01-01T12:00:00+00:00'], // With timezone offset
            ['2023-12-31T23:59:59.999999Z'], // With microseconds
            [str_repeat('2', 20)], // Minimum length
            [str_repeat('3', 64)], // Maximum length
        ];
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z',
            'unknown_key' => 'should_be_ignored'
        ];
        
        $timestamps = new ActivityTimestamps($data);
        
        $this->assertEquals('2023-01-01T12:00:00Z', $timestamps->create_time);
        $this->assertEquals('2023-01-01T13:00:00Z', $timestamps->update_time);
        $this->assertFalse(property_exists($timestamps, 'unknown_key'));
    }

    public function testTimestampsAreOptional(): void
    {
        $timestamps = new ActivityTimestamps([
            'create_time' => null,
            'update_time' => null
        ]);
        
        // Should not throw exception during validation
        $timestamps->validate();
        
        // Should not be included in JSON serialization
        $result = $timestamps->jsonSerialize();
        $this->assertEmpty((array) $result);
    }
}