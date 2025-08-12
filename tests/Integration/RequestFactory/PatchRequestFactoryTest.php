<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use stdClass;

class PatchRequestFactoryTest extends BaseTestCase
{
    private PatchRequestFactory $patchRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patchRequestFactory = new PatchRequestFactory();
    }

    public function testGetOrderPatches(): void
    {
        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(10.00);

        //Currency object
        $currency = Registry::getConfig()->getCurrencyObject('EUR');
        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $orderId = 'testOrderId';

        $patches = $this->patchRequestFactory->getOrderPatches($basketMock, $orderId);

        $this->assertIsArray($patches);
        $this->assertNotEmpty($patches);
    }

    public function testGetShippingAddressPatch(): void
    {
        $deliveryId = 'deliveryId';
        $deliveryAddressMock = $this->createMock(Address::class);
        $deliveryAddressMock->method('load')->willReturn(true);
        $deliveryAddressMock->method('getFieldData')->willReturnMap([
            ['oxstreet', 'Street'],
            ['oxstreetnr', '123'],
            ['oxcompany', 'Company'],
            ['oxaddinfo', 'AddInfo'],
            ['oxcity', 'City'],
            ['oxzip', '12345'],
            ['oxstateid', 'stateId'],
            ['oxcountryid', 'countryId']
        ]);

        $stateMock = $this->createMock(State::class);
        $stateMock->method('getFieldData')->willReturn('State');

        $countryMock = $this->createMock(Country::class);
        $countryMock->oxcountry__oxisoalpha2 = (object) ['value' => 'US'];

        Registry::getSession()->setVariable("deladrid", $deliveryId);

        $this->mockObjectCreation(Address::class, $deliveryAddressMock);
        $this->mockObjectCreation(State::class, $stateMock);
        $this->mockObjectCreation(Country::class, $countryMock);

        $patch = $this->patchRequestFactory->getShippingAddressPatch($deliveryAddressMock);

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_REPLACE, $patch->op);
        $this->assertInstanceOf(AddressPortable::class, $patch->value);
    }

    public function testGetShippingNamePatch(): void
    {
        $deliveryId = 'deliveryId';
        $deliveryAddressMock = $this->createMock(Address::class);
        $deliveryAddressMock->method('load')->willReturn(true);
        Registry::getSession()->setVariable("deladrid", $deliveryId);
        $patch = $this->patchRequestFactory->getShippingNamePatch($deliveryAddressMock);

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_REPLACE, $patch->op);
    }

    public function testGetAmountPatch(): void
    {
        $currency = Registry::getConfig()->getCurrencyObject('EUR');

        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $amountWithBreakdown = new AmountWithBreakdown();
        $amountWithBreakdown->value = 1.00;
        $amountFactoryMock = $this->createMock(PayPalRequestAmountFactory::class);
        $amountFactoryMock->method('getAmount')->willReturn($amountWithBreakdown);

        Registry::set(PayPalRequestAmountFactory::class, $amountFactoryMock);

        $this->patchRequestFactory->getOrderPatches($basketMock);

        $patch = $this->patchRequestFactory->getAmountPatch();

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_REPLACE, $patch->op);
    }

    public function testGetPurchaseUnitsPatch(): void
    {
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(10.00);

        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('getPrice')->willReturn($priceMock);

        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getBasketCurrency')->willReturn((object) ['decimal' => 2]);

        $basketItem = $this->createMock(BasketItem::class);
        $basketItem->method('getTitle')->willReturn('Test Item');
        $article = oxNew(Article::class);
        $basketItem->method('getArticle')->willReturn($article);
        $basketMock->method('getContents')->willReturn([
            $basketItem
        ]);

        $basketMock->method('getPayPalCheckoutWrapping')->willReturn(10.00);
        $basketMock->method('getPayPalCheckoutGiftCard')->willReturn(5.00);
        $basketMock->method('getPayPalCheckoutPayment')->willReturn(2.00);

        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(-1.00);

        $basketMock->method('getPayPalCheckoutRoundDiff')->willReturn(0.01);

        $this->patchRequestFactory->getOrderPatches($basketMock);

        $patch = $this->patchRequestFactory->getPurchaseUnitsPatch();

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_REPLACE, $patch->op);
        $this->assertIsArray($patch->value);
    }

    public function testGetCustomIdPatch(): void
    {
        $orderId = 'testOrderId';

        $patch = $this->patchRequestFactory->getCustomIdPatch($orderId);

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_ADD, $patch->op);
        $this->assertEquals($orderId, $patch->value);
    }

    private function mockObjectCreation(string $className, object $mock): void
    {
        Registry::set($className, $mock);
    }
}
