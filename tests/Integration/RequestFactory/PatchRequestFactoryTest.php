<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;
use OxidSolutionCatalysts\PayPal\Model\Basket;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPal\Model\Basket as PayPalBasket;

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
        // Setup delivery address in session
        $deliveryAddressId = '_test_delivery_address';
        Registry::getSession()->setVariable('deladrid', $deliveryAddressId);

        // Create and save a delivery address
        $deliveryAddress = oxNew(Address::class);
        $deliveryAddress->setId($deliveryAddressId);
        $deliveryAddress->assign([
            'oxid' => $deliveryAddressId,
            'oxuserid' => '_testuser',
            'oxfname' => 'Test',
            'oxlname' => 'User',
            'oxstreet' => 'Test Street',
            'oxstreetnr' => '123',
            'oxcity' => 'Test City',
            'oxzip' => '12345',
            'oxcountryid' => 'a7c40f631fc920687.20179984',
            'oxcompany' => 'Test Company',
            'oxaddinfo' => 'Additional Info'
        ]);
        $deliveryAddress->save();

        // Create price mock with more realistic values
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(100.00);
        $priceMock->method('getNettoPrice')->willReturn(84.03);
        $priceMock->method('getVatValue')->willReturn(15.97);

        // Create a proper currency object
        $currency = new \stdClass();
        $currency->name = 'EUR';
        $currency->rate = 1.0;
        $currency->decimal = 2;
        $currency->thousand = '.';
        $currency->sign = '€';
        $currency->side = 'right';

        // Create basket item mock
        $basketItemMock = $this->createMock(BasketItem::class);
        $basketItemMock->method('getTitle')->willReturn('Test Product');
        $basketItemMock->method('getAmount')->willReturn('2');

        // Create article mock
        $articleMock = $this->createMock(\OxidSolutionCatalysts\PayPal\Model\Article::class);
        $articleMock->method('isVirtualPayPalArticle')->willReturn(false);
        $articleMock->method('getId')->willReturn('test_article_id');

        $basketItemMock->method('getArticle')->willReturn($articleMock);

        // Create unit price mock
        $unitPriceMock = $this->createMock(Price::class);
        $unitPriceMock->method('getBruttoPrice')->willReturn(50.00);
        $unitPriceMock->method('getPrice')->willReturn(50.00);

        $basketItemMock->method('getUnitPrice')->willReturn($unitPriceMock);

        // Create basket mock with all required methods
        $basketMock = $this->createMock(PayPalBasket::class);
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getContents')->willReturn([$basketItemMock]);
        $basketMock->method('getPayPalCheckoutWrapping')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutGiftCard')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutPayment')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutDiscount')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.00);
        $basketMock->method('getPayPalCheckoutRoundDiff')->willReturn(0.00);

        // Create proper amount with breakdown
        $amountWithBreakdown = new AmountWithBreakdown();
        $amountWithBreakdown->value = '100.00';
        $amountWithBreakdown->currency_code = 'EUR';

        // Mock the amount factory
        $amountFactoryMock = $this->createMock(PayPalRequestAmountFactory::class);
        $amountFactoryMock->method('getAmount')->willReturn($amountWithBreakdown);

        $PayPalRequestAmountFactoryBackup = Registry::get(PayPalRequestAmountFactory::class);
        Registry::set(PayPalRequestAmountFactory::class, $amountFactoryMock);

        $orderId = 'testOrderId';


        try {
            $patches = $this->patchRequestFactory->getOrderPatches($basketMock, $orderId);

            $this->assertIsArray($patches);
            $this->assertNotEmpty($patches, 'Patches array should not be empty');

            // Check that we have the expected patches (shipping patches)
            $hasShippingNamePatch = false;
            $hasShippingAddressPatch = false;

            foreach ($patches as $patch) {
                $this->assertInstanceOf(Patch::class, $patch);

                if (strpos($patch->path, '/shipping/name') !== false) {
                    $hasShippingNamePatch = true;
                }
                if (strpos($patch->path, '/shipping/address') !== false) {
                    $hasShippingAddressPatch = true;
                }
            }

            $this->assertTrue($hasShippingNamePatch, 'Should have shipping name patch');
            $this->assertTrue($hasShippingAddressPatch, 'Should have shipping address patch');

        } finally {
            // Clean up test data
            $this->cleanUpTable('oxaddress');
            Registry::getSession()->deleteVariable('deladrid');

            // Restore the original factory
            Registry::set(PayPalRequestAmountFactory::class, $PayPalRequestAmountFactoryBackup);
        }
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
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(1.0);
        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('getPrice')->willReturn($priceMock);
        $basketMock->method('getBasketCurrency')->willReturn($currency);
        $amountWithBreakdown = new AmountWithBreakdown();
        $amountWithBreakdown->value = '1.00';
        $amountWithBreakdown->currency_code = 'EUR';

        $amountFactoryMock = $this->createMock(PayPalRequestAmountFactory::class);
        $amountFactoryMock->method('getAmount')->willReturn($amountWithBreakdown);

        $PayPalRequestAmountFactoryBackup = Registry::get(PayPalRequestAmountFactory::class);
        Registry::set(PayPalRequestAmountFactory::class, $amountFactoryMock);

        $this->patchRequestFactory->getOrderPatches($basketMock);

        $patch = $this->patchRequestFactory->getAmountPatch();

        $this->assertInstanceOf(Patch::class, $patch);
        $this->assertEquals(Patch::OP_REPLACE, $patch->op);

        //restoring globally changed object in the registry
        Registry::set(PayPalRequestAmountFactory::class, $PayPalRequestAmountFactoryBackup);
    }

    public function testGetPurchaseUnitsPatch(): void
    {
        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(10.00);

        $basketMock = $this->createMock(PayPalBasket::class);
        $basketMock->method('getPrice')->willReturn($priceMock);

        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $basketMock->method('getBasketCurrency')->willReturn((object) ['decimal' => 2]);

        $basketItem = $this->createMock(BasketItem::class);
        $article = $this->createMock(\OxidSolutionCatalysts\PayPal\Model\Article::class);

        $article->method('isVirtualPayPalArticle')->willReturn(false);
        $basketItem->method('getArticle')
            ->willReturn($article);
        $basketItem->method('getTitle')->willReturn('Test Product');
        $basketMock->method('getContents')->willReturn([$basketItem]);

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