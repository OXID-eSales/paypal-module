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

    public function testGetAmountBrutMode(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(34.0);

        $basketMock = $this->createMock(PayPalBasket::class);
        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.0);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.0);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.0);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.0);
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
        $this->assertEquals(34.0, $amountWithBreakdown->value);
        $this->assertEquals('EUR', $amountWithBreakdown->currency_code);

        // Assert breakdown properties
        $this->assertInstanceOf(AmountBreakdown::class, $amountWithBreakdown->breakdown);
        $this->assertEquals(30.0, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(4.0, $amountWithBreakdown->breakdown->shipping->value);
    }

    public function testGetAmountShowNetPricesMode(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(34.0);

        $basketMock = $this->createMock(PayPalBasket::class);
        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.0);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.0);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.0);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.0);
        $basketMock->setUser($user);
        $basketMock->setBasketUser($user);
        $basketMock->setPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $basketMock->setShipping('oxidstandard');
        $basketMock->calculateBasket(true);

        // Set up the config mock expectation
        $this->configMock->method('getConfigParam')->with('blShowNetPrice')->willReturn(true);

        // Call the method under test
        $amountWithBreakdown = $this->factory->getAmount($basketMock);

        // Assert the amountWithBreakdown is an AmountWithBreakdown object
        $this->assertInstanceOf(AmountWithBreakdown::class, $amountWithBreakdown);

        // Assert the values are as expected
        $this->assertEquals(34.0, $amountWithBreakdown->value);
        $this->assertEquals('EUR', $amountWithBreakdown->currency_code);

        // Assert breakdown properties
        $this->assertInstanceOf(AmountBreakdown::class, $amountWithBreakdown->breakdown);
        $this->assertEquals(30.0, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(4.0, $amountWithBreakdown->breakdown->shipping->value);
    }

    public function testGetAmountBrutModeWithPrecisionAboveLimit(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(34.2469);

        $basketMock = $this->createMock(PayPalBasket::class);
        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.0);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.13);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.0);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.12345);
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
        $this->assertEquals(34.25, $amountWithBreakdown->value);
        $this->assertEquals('EUR', $amountWithBreakdown->currency_code);

        // Assert breakdown properties
        $this->assertInstanceOf(AmountBreakdown::class, $amountWithBreakdown->breakdown);
        $this->assertEquals(30.13, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(4.12, $amountWithBreakdown->breakdown->shipping->value);
    }

    public function testGetAmountBrutModeWithDiscount(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(24.0);

        $basketMock = $this->createMock(PayPalBasket::class);
        // Set up the basket mock expectations
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(10.0);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.0);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(0.0);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(4.0);
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
        $this->assertEquals(24.0, $amountWithBreakdown->value);
        $this->assertEquals('EUR', $amountWithBreakdown->currency_code);

        // Assert breakdown properties
        $this->assertInstanceOf(AmountBreakdown::class, $amountWithBreakdown->breakdown);
        $this->assertEquals(30.0, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(4.0, $amountWithBreakdown->breakdown->shipping->value);
    }

    public function testGetAmountWithNoShipping(): void
    {
        $basketMock = $this->createMock(PayPalBasket::class);

        $currency = new stdClass();
        $currency->name = 'EUR';

        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(30.00);

        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.0);
        $basketMock->method('getPayPalCheckoutItems')->willReturn(30.00);
        $basketMock->method('getAdditionalPayPalCheckoutItemCosts')->willReturn(10.00);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.00); // No shipping

        $this->configMock->method('getConfigParam')->with('blEnterNetPrice')->willReturn(false);

        $amountWithBreakdown = $this->factory->getAmount($basketMock);

        $this->assertEquals(30.0, $amountWithBreakdown->breakdown->item_total->value);
        $this->assertEquals(0, $amountWithBreakdown->breakdown->shipping->value);
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
        $basketMock = $this->createMock(PayPalBasket::class);

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
}
