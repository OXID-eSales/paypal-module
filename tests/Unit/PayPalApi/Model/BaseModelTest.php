<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model;

use Error;
use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use PHPUnit\Framework\TestCase;

class BaseModelTest extends TestCase
{
    private object $testModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an anonymous class that uses BaseModel trait for testing
        $this->testModel = new class implements JsonSerializable {
            use BaseModel;
            
            public $property1 = 'value1';
            public $property2 = null;
            public $property3 = 'value3';
        };
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $result = $this->testModel->jsonSerialize();
        
        $this->assertIsObject($result);
        $this->assertTrue(property_exists($result, 'property1'));
        $this->assertFalse(property_exists($result, 'property2')); // null should be filtered out
        $this->assertTrue(property_exists($result, 'property3'));
        $this->assertEquals('value1', $result->property1);
        $this->assertEquals('value3', $result->property3);
    }

    public function testJsonSerializeWithAllNullValues(): void
    {
        $emptyModel = new class implements JsonSerializable {
            use BaseModel;
            
            public $property1 = null;
            public $property2 = null;
        };
        
        $result = $emptyModel->jsonSerialize();
        
        $this->assertIsObject($result);
        $this->assertEmpty((array) $result);
    }

    public function testJsonSerializeWithAllSetValues(): void
    {
        $fullModel = new class implements JsonSerializable {
            use BaseModel;
            
            public $property1 = 'value1';
            public $property2 = 'value2';
            public $property3 = 0; // falsy but not null
            public $property4 = false; // falsy but not null
        };
        
        $result = $fullModel->jsonSerialize();
        
        $this->assertIsObject($result);
        $this->assertCount(4, (array) $result);
        $this->assertEquals('value1', $result->property1);
        $this->assertEquals('value2', $result->property2);
        $this->assertEquals(0, $result->property3);
        $this->assertEquals(false, $result->property4);
    }

    public function testMagicSetThrowsError(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cant set nonExistentProperty on');
        
        $this->testModel->nonExistentProperty = 'value';
    }

    public function testMagicSetThrowsErrorWithCorrectClassName(): void
    {
        $namedModel = new class implements JsonSerializable {
            use BaseModel;
        };
        
        $this->expectException(Error::class);
        $this->expectExceptionMessageMatches('/Cant set testProperty on .*@anonymous.*/');
        
        $namedModel->testProperty = 'value';
    }

    public function testExperienceContextPropertyExists(): void
    {
        $this->assertTrue(property_exists($this->testModel, 'experience_context'));
        $this->assertNull($this->testModel->experience_context);
    }
}