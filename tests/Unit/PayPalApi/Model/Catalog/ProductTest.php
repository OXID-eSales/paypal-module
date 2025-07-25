<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Model\Catalog;

use InvalidArgumentException;
use OxidSolutionCatalysts\PayPalApi\Model\Catalog\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'id' => 'PROD-123',
            'name' => 'Test Product',
            'description' => 'A test product description',
            'type' => 'PHYSICAL',
            'category' => 'SOFTWARE',
            'image_url' => 'https://example.com/image.jpg',
            'home_url' => 'https://example.com/product'
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD-123', $product->id);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('A test product description', $product->description);
        $this->assertEquals('PHYSICAL', $product->type);
        $this->assertEquals('SOFTWARE', $product->category);
        $this->assertEquals('https://example.com/image.jpg', $product->image_url);
        $this->assertEquals('https://example.com/product', $product->home_url);
    }

    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'name' => 'Minimal Product'
        ];
        
        $product = new Product($data);
        
        $this->assertNull($product->id);
        $this->assertEquals('Minimal Product', $product->name);
        $this->assertNull($product->description);
        $this->assertEquals('PHYSICAL', $product->type); // default value
        $this->assertNull($product->category);
        $this->assertNull($product->image_url);
        $this->assertNull($product->home_url);
        $this->assertNull($product->create_time);
        $this->assertNull($product->update_time);
        $this->assertNull($product->links);
    }

    public function testConstructorWithNoData(): void
    {
        $product = new Product();
        
        $this->assertNull($product->id);
        $this->assertNull($product->name);
        $this->assertNull($product->description);
        $this->assertEquals('PHYSICAL', $product->type); // default value
        $this->assertNull($product->category);
        $this->assertNull($product->image_url);
        $this->assertNull($product->home_url);
    }

    public function testConstructorWithNullData(): void
    {
        $product = new Product(null);
        
        $this->assertNull($product->id);
        $this->assertNull($product->name);
        $this->assertNull($product->description);
        $this->assertEquals('PHYSICAL', $product->type); // default value
    }

    public function testJsonSerializeFiltersNullValues(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'type' => 'DIGITAL'
        ]);
        
        $result = $product->jsonSerialize();
        
        $this->assertTrue(property_exists($result, 'name'));
        $this->assertTrue(property_exists($result, 'type'));
        $this->assertFalse(property_exists($result, 'id'));
        $this->assertFalse(property_exists($result, 'description'));
        $this->assertFalse(property_exists($result, 'category'));
        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals('DIGITAL', $result->type);
    }

    public function testJsonSerializeWithAllValues(): void
    {
        $product = new Product([
            'id' => 'PROD-FULL',
            'name' => 'Full Product',
            'description' => 'Complete product with all fields',
            'type' => 'SERVICE',
            'category' => 'CONSULTING',
            'image_url' => 'https://example.com/full.jpg',
            'home_url' => 'https://example.com/full',
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z'
        ]);
        
        $result = $product->jsonSerialize();
        
        $this->assertEquals('PROD-FULL', $result->id);
        $this->assertEquals('Full Product', $result->name);
        $this->assertEquals('Complete product with all fields', $result->description);
        $this->assertEquals('SERVICE', $result->type);
        $this->assertEquals('CONSULTING', $result->category);
        $this->assertEquals('https://example.com/full.jpg', $result->image_url);
        $this->assertEquals('https://example.com/full', $result->home_url);
        $this->assertEquals('2023-01-01T12:00:00Z', $result->create_time);
        $this->assertEquals('2023-01-01T13:00:00Z', $result->update_time);
    }

    public function testValidateWithValidData(): void
    {
        $product = new Product([
            'name' => 'Valid Product',
            'type' => 'PHYSICAL',
            'category' => 'SOFTWARE'
        ]);
        
        // Should not throw exception
        $product->validate();
        $this->assertTrue(true);
    }

    public function testValidateWithoutNameIsValid(): void
    {
        $product = new Product([
            'type' => 'PHYSICAL'
        ]);
        
        // Name is not required, should not throw exception
        $product->validate();
        $this->assertTrue(true);
    }

    public function testValidateNameTooShort(): void
    {
        $product = new Product([
            'name' => '', // Empty string, min is 1
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Product must have minlength of 1');
        
        $product->validate();
    }

    public function testValidateNameTooLong(): void
    {
        $product = new Product([
            'name' => str_repeat('a', 128), // 128 characters, max is 127
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Product must have maxlength of 127');
        
        $product->validate();
    }

    public function testValidateDescriptionTooLong(): void
    {
        $product = new Product([
            'name' => 'Valid Product',
            'description' => str_repeat('a', 257), // 257 characters, max is 256
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('description in Product must have maxlength of 256');
        
        $product->validate();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validTypesProvider')]
    public function testValidTypes(string $type): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'type' => $type
        ]);
        
        $product->validate();
        $this->assertEquals($type, $product->type);
    }

    public static function validTypesProvider(): array
    {
        return [
            [Product::TYPE_PHYSICAL],
            [Product::TYPE_DIGITAL],
            [Product::TYPE_SERVICE],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validCategoriesProvider')]
    public function testValidCategories(string $category): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'type' => 'PHYSICAL',
            'category' => $category
        ]);
        
        $product->validate();
        $this->assertEquals($category, $product->category);
    }

    public static function validCategoriesProvider(): array
    {
        return [
            [Product::CATEGORY_SOFTWARE],
            [Product::CATEGORY_DIGITAL_GAMES],
            [Product::CATEGORY_BOOKS_PERIODICALS_AND_NEWSPAPERS],
            [Product::CATEGORY_ACADEMIC_SOFTWARE],
            [Product::CATEGORY_COMPUTER_HARDWARE_AND_SOFTWARE],
        ];
    }

    public function testValidateImageUrlTooLong(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'image_url' => 'https://example.com/' . str_repeat('a', 2000), // Too long URL
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('image_url in Product must have maxlength of 2000');
        
        $product->validate();
    }

    public function testValidateHomeUrlTooLong(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'home_url' => 'https://example.com/' . str_repeat('a', 2000), // Too long URL
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('home_url in Product must have maxlength of 2000');
        
        $product->validate();
    }

    public function testValidateWithFromParameter(): void
    {
        $product = new Product([
            'name' => '', // Empty string to trigger minlength validation
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name in Product must have minlength of 1 within TestContext');
        
        $product->validate('TestContext');
    }

    public function testValidateIdTooLong(): void
    {
        $product = new Product([
            'id' => str_repeat('a', 51), // 51 characters, max is 50
            'name' => 'Test Product',
            'type' => 'PHYSICAL'
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('id in Product must have maxlength of 50');
        
        $product->validate();
    }

    public function testMapIgnoresUnknownKeys(): void
    {
        $data = [
            'id' => 'PROD-UNKNOWN',
            'name' => 'Test Product',
            'unknown_field' => 'should_be_ignored',
            'another_unknown' => 'also_ignored'
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD-UNKNOWN', $product->id);
        $this->assertEquals('Test Product', $product->name);
        $this->assertFalse(property_exists($product, 'unknown_field'));
        $this->assertFalse(property_exists($product, 'another_unknown'));
    }

    public function testExperienceContextPropertyExists(): void
    {
        $product = new Product();
        
        $this->assertTrue(property_exists($product, 'experience_context'));
        $this->assertNull($product->experience_context);
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals('PHYSICAL', Product::TYPE_PHYSICAL);
        $this->assertEquals('DIGITAL', Product::TYPE_DIGITAL);
        $this->assertEquals('SERVICE', Product::TYPE_SERVICE);
    }

    public function testCategoryConstants(): void
    {
        $this->assertEquals('AC_REFRIGERATION_REPAIR', Product::CATEGORY_AC_REFRIGERATION_REPAIR);
        $this->assertEquals('ACADEMIC_SOFTWARE', Product::CATEGORY_ACADEMIC_SOFTWARE);
        $this->assertEquals('ACCESSORIES', Product::CATEGORY_ACCESSORIES);
        $this->assertEquals('ACCOUNTING', Product::CATEGORY_ACCOUNTING);
        $this->assertEquals('ADULT', Product::CATEGORY_ADULT);
        $this->assertEquals('ADVERTISING', Product::CATEGORY_ADVERTISING);
    }

    public function testComplexProductWithLinksAndTimestamps(): void
    {
        $complexData = [
            'id' => 'PROD-COMPLEX',
            'name' => 'Complex Product',
            'description' => 'A complex product with all possible fields',
            'type' => 'DIGITAL',
            'category' => 'SOFTWARE',
            'image_url' => 'https://example.com/complex.jpg',
            'home_url' => 'https://example.com/complex',
            'create_time' => '2023-01-01T12:00:00Z',
            'update_time' => '2023-01-01T13:00:00Z',
            'links' => [
                [
                    'href' => 'https://api.paypal.com/v1/catalogs/products/PROD-COMPLEX',
                    'rel' => 'self',
                    'method' => 'GET'
                ]
            ]
        ];
        
        $product = new Product($complexData);
        
        $this->assertEquals('PROD-COMPLEX', $product->id);
        $this->assertEquals('Complex Product', $product->name);
        $this->assertEquals('A complex product with all possible fields', $product->description);
        $this->assertEquals('DIGITAL', $product->type);
        $this->assertEquals('SOFTWARE', $product->category);
        
        // Verify validation passes for complex valid data
        $product->validate();
        $this->assertTrue(true);
        
        // Verify JSON serialization includes all data
        $result = $product->jsonSerialize();
        $this->assertEquals('PROD-COMPLEX', $result->id);
        $this->assertEquals('Complex Product', $result->name);
        $this->assertEquals('DIGITAL', $result->type);
    }
}