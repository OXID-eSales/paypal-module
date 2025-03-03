<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;
use stdClass;
use OxidSolutionCatalysts\PayPal\Model\Basket as PayPalBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;

class PayPalRequestAmountFactoryTest extends BaseTestCase
{
    protected const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    protected const TEST_PRODUCT_ID = '1126';

    private PayPalRequestAmountFactory $factory;
    private Config $configMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new PayPalRequestAmountFactory();

        // Mock the Config class
        $this->configMock = $this->createMock(Config::class);

        // Use reflection to replace the config property
        $reflectionClass = new \ReflectionClass(PayPalRequestAmountFactory::class);
        $configProperty = $reflectionClass->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->factory, $this->configMock);
    }

    public function testGetAmountStd(): void
    {
        //DE demo user
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        //Currency object
        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(34.000);

        // Create mock for Basket
        $basketMock = $this->createMock(Basket::class);
        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(true); // Net mode
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.00);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.00);
        $basketMock->setUser($user);
        $basketMock->setBasketUser($user);
        $basketMock->setPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $basketMock->setShipping('oxidstandard');
        $basketMock->calculateBasket(true);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $amountWithBreakdown = $this->factory->getAmount($basketMock);

        // Assert the amountWithBreakdown is an AmountWithBreakdown object
        $this->assertInstanceOf(AmountWithBreakdown::class, $amountWithBreakdown);

        // Assert the values are as expected
        $this->assertEquals(34.00, $amountWithBreakdown->value);
        $this->assertEquals('EUR', $amountWithBreakdown->currency_code);

        // Assert breakdown properties
        $this->assertInstanceOf(AmountBreakdown::class, $amountWithBreakdown->breakdown);
        $this->assertEquals(30.00, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(4.00, $amountWithBreakdown->breakdown->shipping->value);
    }

    public function testGetAmountWithPrecisionAboveLimit(): void
    {
        // Create mock for Basket
        $basketMock = $this->createMock(Basket::class);

        // Create currency object with higher precision
        $currency = new stdClass();
        $currency->name = 'JPY';
        $currency->decimal = 3; // Above PayPal's limit

        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(34.000);

        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(true); // Net mode
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.000);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.000);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $result = $this->factory->getAmount($basketMock);

        // Assert the values with high precision currency
        $this->assertEquals($basketMock->getPrice()->getBruttoPrice(), $result->value);
        $this->assertEquals('JPY', $result->currency_code);

        // In net mode with precision above limit, shipping should be null and combined with item_total
        $this->assertNull($result->breakdown->shipping);
        $this->assertEquals($basketMock->getPrice()->getBruttoPrice(), $result->breakdown->item_total->value);
    }

    public function testGetAmountWithDiscount(): void
    {
        // Create mock for Basket
        $basketMock = $this->createMock(Basket::class);

        //Currency object
        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(100.00);

        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(90.00);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(10.00);
        //$basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(10.00);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $result = $this->factory->getAmount($basketMock);

        // Assert the result has no discount field set
        $this->assertNotNull($result->breakdown->discount);
        $this->assertEquals(100.00, $result->value);
        $this->assertEquals(10.00, $result->breakdown->discount->value);
        $this->assertEquals(90.00, $result->breakdown->item_total->value);
        $this->assertEquals(10.00, $result->breakdown->shipping->value);
    }

    public function testGetAmountWithNoShipping(): void
    {
        // Create mock for Basket
        $basketMock = $this->createMock(PayPalBasket::class);

        // Create currency object
        $currency = new stdClass();
        $currency->name = 'EUR';

        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(100.00);

        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(10.00);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(100.00);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(10.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.00); // No shipping

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $result = $this->factory->getAmount($basketMock);

        // Assert shipping is null when there are no delivery costs
        $this->assertNull($result->breakdown->shipping);
    }

    public function testSetCurrency(): void
    {
        // Create currency object
        $currency = new stdClass();
        $currency->name = 'GBP';

        // Set currency
        $this->factory->setCurrency($currency);

        // Get currency and verify it has been set correctly
        $result = $this->factory->getCurrency();
        $this->assertEquals('GBP', $result->name);
        $this->assertEquals(2, $result->decimal);
    }

    public function testNegativeDiscountHandling(): void
    {
        // Create mock for Basket
        $basketMock = $this->createMock(Basket::class);

        // Create currency object
        $currency = new stdClass();
        $currency->name = 'EUR';

        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(100.00);

        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false); // Brutto mode
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(-10.00); // Negative discount
        $basketMock->method('getPayPalCheckoutItems')->willReturn(90.00);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(10.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(10.00);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $result = $this->factory->getAmount($basketMock);

        // In brutto mode, negative discount should be set to 0
        $this->assertEquals(100.00, $result->value);
        $this->assertEquals(100.00, $basketMock->getPayPalCheckoutItems() +
            $basketMock->getAdditionalPayPalCheckoutItemCosts());
    }

    public function testNetModeWithPriceSurcharge(): void
    {
        // Create mock for Basket
        $basketMock = $this->createMock(Basket::class);

        // Create currency object
        $currency = new stdClass();
        $currency->name = 'EUR';

        // Create price mock - set to a value that would cause negative brutDiscountValue
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(110.00); // More than items + additional costs

        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(true); // Net mode
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(10.00);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(90.00);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(10.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(10.00);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        // Call the method under test
        $result = $this->factory->getAmount($basketMock);

        // Calculate the expected brutDiscountValue
        $brutDiscountValue = $basketMock->getPayPalCheckoutItems() +
            $basketMock->getAdditionalPayPalCheckoutItemCosts() -
            $priceMock->getBruttoPrice();

        // In net mode with negative brutDiscountValue, the discount should be 0
        $this->assertEquals(110.00, $result->value);
        $this->assertLessThan(0, $brutDiscountValue); // Verify brutDiscountValue is negative

        // Since discount should be 0 in this case, no discount property should be set
        $this->assertObjectHasAttribute('discount', $result->breakdown);
        $this->assertEquals(0, $result->breakdown->discount->value);
    }
}