<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\tests\Integration\Service\Factory;

use OxidSolutionCatalysts\PayPal\Model\Article;
use OxidSolutionCatalysts\PayPal\Model\Basket;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use ReflectionClass;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Config;

/**
 * Unit tests for PayPalPurchaseUnitsFactory helper mechanisms.
 *
 * Note: We focus on the internal pure helpers to avoid requiring a full OXID runtime.
 */
class PayPalPurchaseUnitsFactoryTest extends BaseTestCase
{
    private function makeFactory(): PayPalPurchaseUnitsFactory
    {
        // Create a PHPUnit mock for ModuleSettings so the factory can be instantiated.
        $moduleSettings = $this->getMockBuilder(ModuleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShopName'])
            ->getMock();
        $moduleSettings->method('getShopName')->willReturn('Test Shop');

        return new PayPalPurchaseUnitsFactory($moduleSettings);
    }

    private function callPrivate(object $object, string $methodName, array $args = [])
    {
        $ref = new ReflectionClass($object);
        $m = $ref->getMethod($methodName);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $args);
    }

    public function testSumTaxFromItems_SumsQuantityTimesTaxPerUnitWithRounding(): void
    {
        $factory = $this->makeFactory();

        $items = [
            // qty 2, tax 0.195 -> 0.39
            [
                'quantity' => '2',
                'tax' => ['currency_code' => 'EUR', 'value' => '0.195'],
            ],
            // qty 3, tax 0.333 -> 0.999 -> rounds to 1.00
            [
                'quantity' => '3',
                'tax' => ['currency_code' => 'EUR', 'value' => '0.333'],
            ],
            // missing tax -> contributes 0.00
            [
                'quantity' => '5',
            ],
        ];

        $sum = $this->callPrivate($factory, 'sumTaxFromItems', [$items]);
        // Expected: 0.39 + 1.00 = 1.39
        $this->assertSame(1.39, $sum);
    }

    public function testMapAmountBreakdown_MapsKnownKeysAndFormatsValues(): void
    {
        $factory = $this->makeFactory();

        $bdArr = [
            'item_total' => ['currency_code' => 'EUR', 'value' => '9.999'], // -> 10.00
            'shipping' => ['currency_code' => 'EUR', 'value' => 2], // -> 2.00
            'tax_total' => ['currency_code' => 'EUR', 'value' => 0.1], // -> 0.10
            'handling' => ['currency_code' => 'EUR', 'value' => 0],
            'insurance' => ['currency_code' => 'EUR', 'value' => '0'],
            'shipping_discount' => ['currency_code' => 'EUR', 'value' => '0.005'], // -> 0.01
            'discount' => ['currency_code' => 'EUR', 'value' => '1.234'], // -> 1.23
            'unknown_key' => ['currency_code' => 'EUR', 'value' => '999'], // ignored
        ];

        /** @var AmountBreakdown $mapped */
        $mapped = $this->callPrivate($factory, 'mapAmountBreakdown', [$bdArr, 'EUR']);

        $this->assertInstanceOf(AmountBreakdown::class, $mapped);
        $this->assertSame('EUR', $mapped->item_total->currency_code);
        $this->assertSame('10.00', $mapped->item_total->value);
        $this->assertSame('2.00', $mapped->shipping->value);
        $this->assertSame('0.10', $mapped->tax_total->value);
        $this->assertSame('0.00', $mapped->handling->value);
        $this->assertSame('0.00', $mapped->insurance->value);
        $this->assertSame('0.01', $mapped->shipping_discount->value);
        $this->assertSame('1.23', $mapped->discount->value);
        $this->assertObjectNotHasProperty('unknown_key', $mapped);
    }

    public function testMapAmountWithBreakdown_MapsAmountAndNestedBreakdown(): void
    {
        $factory = $this->makeFactory();

        $amountArr = [
            'currency_code' => 'EUR',
            'value' => '12.345', // -> 12.35
            'breakdown' => [
                'item_total' => ['currency_code' => 'EUR', 'value' => '10.00'],
                'shipping' => ['currency_code' => 'EUR', 'value' => '2.00'],
                'tax_total' => ['currency_code' => 'EUR', 'value' => '0.345'], // -> 0.35
                'discount' => ['currency_code' => 'EUR', 'value' => '0.00'],
            ],
        ];

        /** @var AmountWithBreakdown $mapped */
        $mapped = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);

        $this->assertInstanceOf(AmountWithBreakdown::class, $mapped);
        $this->assertSame('EUR', $mapped->currency_code);
        $this->assertSame('12.35', $mapped->value);
        $this->assertInstanceOf(AmountBreakdown::class, $mapped->breakdown);
        $this->assertSame('10.00', $mapped->breakdown->item_total->value);
        $this->assertSame('2.00', $mapped->breakdown->shipping->value);
        $this->assertSame('0.35', $mapped->breakdown->tax_total->value);
        $this->assertSame('0.00', $mapped->breakdown->discount->value);
    }

    public function testBasketBruttoTotalMatchesPurchaseUnitAmountValue(): void
    {
        $factory = $this->makeFactory();

        // Create a Basket mock with controlled responses
        $basket = $this->getMockBuilder(Basket::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getBasketCurrency',
                'getPrice',
                'getPayPalCheckoutDeliveryCosts',
                'getPayPalCheckoutDiscountBrutto',
                'getContents',
            ])
            ->getMock();

        // Currency with 2 decimals and EUR code
        $currency = (object) ['name' => 'EUR', 'decimal' => 2];
        $basket->method('getBasketCurrency')->willReturn($currency);

        // Price stub returning a known brutto price
        $priceStub = new class {
            public function getBruttoPrice()
            {
                return 123.45;
            }
        };
        $basket->method('getPrice')->willReturn($priceStub);

        // Shipping and discount used in breakdown, but not in amount->value check
        $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(5.00);
        $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(2.00);

        // No basket items needed for this assertion
        $basket->method('getContents')->willReturn([]);

        // Build amount array and map to API model
        $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, []]);
        /** @var AmountWithBreakdown $amount */
        $amount = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);

        // Assert amount->value equals the basket brutto price formatted to 2 decimals
        $this->assertSame('123.45', $amount->value);
        $this->assertSame('EUR', $amount->currency_code);
    }

    public function testBreakdownItemTotalMatchesSumOfBasketItems(): void
    {
        $factory = $this->makeFactory();

        // Mock Basket
        $basket = $this->getMockBuilder(Basket::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getBasketCurrency',
                'getPrice',
                'getPayPalCheckoutDeliveryCosts',
                'getPayPalCheckoutDiscountBrutto',
                'getContents',
            ])
            ->getMock();

        $currency = (object) ['name' => 'EUR', 'decimal' => 2];
        $basket->method('getBasketCurrency')->willReturn($currency);
        // total not relevant for item_total assertion
        $priceStub = new class {
            public function getBruttoPrice()
            {
                return 999.99;
            }
        };
        $basket->method('getPrice')->willReturn($priceStub);
        $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.00);
        $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.00);

        // Create two BasketItem stubs with unit prices and quantities
        $makeItem = function (float $unitPrice, float $qty) {
            return new class ($unitPrice, $qty) {
                private float $unitPrice;
                private float $qty;

                public function __construct($p, $q)
                {
                    $this->unitPrice = $p;
                    $this->qty = $q;
                }

                public function getUnitPrice()
                {
                    return new class ($this->unitPrice) {
                        private float $p;

                        public function __construct($p)
                        {
                            $this->p = $p;
                        }

                        public function getPrice()
                        {
                            return $this->p;
                        }
                    };
                }

                public function getAmount()
                {
                    return (string)$this->qty;
                }
            };
        };
        $item1 = $makeItem(10.00, 2);    // 20.00
        $item2 = $makeItem(2.345, 3);    // 7.035 -> combined sum 27.035 -> rounds to 27.04
        $basket->method('getContents')->willReturn([$item1, $item2]);

        $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, []]);
        $this->assertArrayHasKey('breakdown', $amountArr);
        $this->assertArrayHasKey('item_total', $amountArr['breakdown']);
        $this->assertSame('EUR', $amountArr['breakdown']['item_total']['currency_code']);
        $this->assertSame('27.04', $amountArr['breakdown']['item_total']['value']);
    }

    public function testTaxTotalIsCalculatedFromItemsInNetMode(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force net mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', true);

        try {
            // Basket mock with one item: netto 100, brutto 119, VAT 19%, qty 2
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR', 'decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.0);
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
            $priceStub = new class {
                public function getBruttoPrice()
                {
                    return 238.00;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            // UnitPrice stub with both net and gross exposed + vat percent
            $unitPrice = new class {
                public function getNettoPrice()
                {
                    return 100.00;
                }

                public function getBruttoPrice()
                {
                    return 119.00;
                }

                public function getVat()
                {
                    return 19.0;
                }

                public function getPrice()
                {
                    return 100.00; // used by buildAmountArray item_total sum in net mode context
                }
            };

            // BasketItem mock: use PHPUnit mock of real BasketItem class to satisfy instanceof checks
            $basketItem = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem->method('getTitle')->willReturn('Item A');
            $basketItem->method('getAmount')->willReturn(2);
            $basketItem->method('getUnitPrice')->willReturn($unitPrice);
            $article = $this->createMock(Article::class);
            $article->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem->method('getArticle')->willReturn($article);
            $basket->method('getContents')->willReturn([$basketItem]);

            // Map items (net mode should compute per-unit tax = 19.00)
            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            $this->assertNotEmpty($itemsArr);
            $this->assertSame('19.00', $itemsArr[0]['tax']['value']);

            // Tax total: qty 2 * 19.00 = 38.00
            $taxSum = $this->callPrivate($factory, 'sumTaxFromItems', [$itemsArr]);
            $this->assertSame(38.00, $taxSum);

            // Build amount with items for tax_total
            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            $this->assertSame('38.00', $amountArr['breakdown']['tax_total']['value']);
            $this->assertSame('EUR', $amountArr['breakdown']['tax_total']['currency_code']);
        } finally {
            // restore config
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testTaxTotalIsZeroInGrossMode(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force gross mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', false);

        try {
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR', 'decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.0);
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
            $priceStub = new class {
                public function getBruttoPrice()
                {
                    return 119.00;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            $unitPrice = new class {
                public function getNettoPrice()
                {
                    return 100.00;
                }

                public function getBruttoPrice()
                {
                    return 119.00;
                }

                public function getVat()
                {
                    return 19.0;
                }

                public function getPrice()
                {
                    return 119.00;
                }
            };

            $basketItem = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem->method('getTitle')->willReturn('Item A');
            $basketItem->method('getAmount')->willReturn(1);
            $basketItem->method('getUnitPrice')->willReturn($unitPrice);
            $article = $this->createMock(Article::class);
            $article->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem->method('getArticle')->willReturn($article);
            $basket->method('getContents')->willReturn([$basketItem]);

            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            $this->assertNotEmpty($itemsArr);
            // In gross mode, per-unit tax should be '0.00'
            $this->assertSame('0.00', $itemsArr[0]['tax']['value']);

            $taxSum = $this->callPrivate($factory, 'sumTaxFromItems', [$itemsArr]);
            $this->assertSame(0.00, $taxSum);

            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            $this->assertSame('0.00', $amountArr['breakdown']['tax_total']['value']);
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testHighPrecisionItemTotalsAreRoundedToTwoDecimals(): void
    {
        $factory = $this->makeFactory();

        // Basket mock
        $basket = $this->getMockBuilder(Basket::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
            ->getMock();
        $currency = (object)['name' => 'EUR', 'decimal' => 2];
        $basket->method('getBasketCurrency')->willReturn($currency);
        $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.0);
        $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
        $priceStub = new class {
            public function getBruttoPrice()
            {
                return 999.99; // not used for item_total
            }
        };
        $basket->method('getPrice')->willReturn($priceStub);

        // Two high-precision items: 0.123456789 * 9 = 1.111111101, 1.999999999 * 3 = 5.999999997 => sum 7.111111098 -> 7.11
        $makeItem = function (float $unitPrice, float $qty) {
            return new class ($unitPrice, $qty) {
                private float $unitPrice;
                private float $qty;

                public function __construct($p, $q)
                {
                    $this->unitPrice = $p;
                    $this->qty = $q;
                }

                public function getUnitPrice()
                {
                    return new class ($this->unitPrice) {
                        private float $p;

                        public function __construct($p)
                        {
                            $this->p = $p;
                        }

                        public function getPrice()
                        {
                            return $this->p;
                        }
                    };
                }

                public function getAmount()
                {
                    return (string)$this->qty;
                }
            };
        };
        $item1 = $makeItem(0.123456789, 9);
        $item2 = $makeItem(1.999999999, 3);
        $basket->method('getContents')->willReturn([$item1, $item2]);

        $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, []]);
        $this->assertArrayHasKey('breakdown', $amountArr);
        $this->assertArrayHasKey('item_total', $amountArr['breakdown']);
        $this->assertSame('EUR', $amountArr['breakdown']['item_total']['currency_code']);
        $this->assertSame('7.11', $amountArr['breakdown']['item_total']['value']);
    }

    public function testHighPrecisionTaxTotalFromItemsInNetMode(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force net mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', true);

        try {
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR', 'decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.0);
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
            $priceStub = new class {
                public function getBruttoPrice()
                {
                    return 1.00;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            // UnitPrice stubs with high-precision net values and VATs
            $unitPrice1 = new class {
                public function getNettoPrice()
                {
                    return 0.123456789;
                }

                public function getBruttoPrice()
                {
                    return 0.147;
                }

                public function getVat()
                {
                    return 19.0;
                }

                public function getPrice()
                {
                    return 0.123456789;
                }
            };
            $unitPrice2 = new class {
                public function getNettoPrice()
                {
                    return 1.999999999;
                }

                public function getBruttoPrice()
                {
                    return 2.139999999;
                }

                public function getVat()
                {
                    return 7.0;
                }

                public function getPrice()
                {
                    return 1.999999999;
                }
            };

            $basketItem1 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem1->method('getTitle')->willReturn('A');
            $basketItem1->method('getAmount')->willReturn(9);
            $basketItem1->method('getUnitPrice')->willReturn($unitPrice1);
            $article1 = $this->createMock(Article::class);
            $article1->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem1->method('getArticle')->willReturn($article1);
            $basketItem2 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem2->method('getTitle')->willReturn('B');
            $basketItem2->method('getAmount')->willReturn(3);
            $basketItem2->method('getUnitPrice')->willReturn($unitPrice2);
            $article2 = $this->createMock(Article::class);
            $article2->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem2->method('getArticle')->willReturn($article2);
            $basket->method('getContents')->willReturn([$basketItem1, $basketItem2]);

            // Map items and verify per-unit taxes were rounded to 2 decimals
            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            $this->assertSame('0.02', $itemsArr[0]['tax']['value']); // 0.123456789 * 19% => 0.023... -> 0.02
            $this->assertSame('0.14', $itemsArr[1]['tax']['value']); // 1.999999999 * 7% => 0.1399999999 -> 0.14

            // Sum tax: 9*0.02 + 3*0.14 = 0.18 + 0.42 = 0.60
            $taxSum = $this->callPrivate($factory, 'sumTaxFromItems', [$itemsArr]);
            $this->assertSame(0.60, $taxSum);

            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            $this->assertSame('0.60', $amountArr['breakdown']['tax_total']['value']);
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testMapAmountBreakdownHighPrecisionValuesRoundToTwoDecimals(): void
    {
        $factory = $this->makeFactory();
        $bdArr = [
            'item_total' => ['currency_code' => 'EUR', 'value' => '1.23456789'], // -> 1.23
            'shipping' => ['currency_code' => 'EUR', 'value' => '0.009999999'], // -> 0.01
            'tax_total' => ['currency_code' => 'EUR', 'value' => '0.666666666'], // -> 0.67
            'handling' => ['currency_code' => 'EUR', 'value' => '0.000000004'], // -> 0.00
            'insurance' => ['currency_code' => 'EUR', 'value' => '9.999999999'], // -> 10.00
            'shipping_discount' => ['currency_code' => 'EUR', 'value' => '0.555555555'], // -> 0.56
            'discount' => ['currency_code' => 'EUR', 'value' => '3.1415926535'], // -> 3.14
        ];
        /** @var AmountBreakdown $mapped */
        $mapped = $this->callPrivate($factory, 'mapAmountBreakdown', [$bdArr, 'EUR']);
        $this->assertSame('1.23', $mapped->item_total->value);
        $this->assertSame('0.01', $mapped->shipping->value);
        $this->assertSame('0.67', $mapped->tax_total->value);
        $this->assertSame('0.00', $mapped->handling->value);
        $this->assertSame('10.00', $mapped->insurance->value);
        $this->assertSame('0.56', $mapped->shipping_discount->value);
        $this->assertSame('3.14', $mapped->discount->value);
    }

    public function testAmountValueEqualsBreakdownSumWithHighPrecisionMultipleItemsAndCharges(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force net mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', true);

        try {
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR','decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            // High precision shipping/discount to provoke rounding
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.009999999); // -> 0.01
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.004999999); // -> 0.00

            // Unit prices (net) with many decimals
            $unitPrice1 = new class {
                public function getNettoPrice()
                {
                    return 0.123456789;
                }

                public function getBruttoPrice()
                {
                    return 0.147;
                }

                public function getVat()
                {
                    return 19.0;
                }

                public function getPrice()
                {
                    return 0.123456789;
                }
            };
            $unitPrice2 = new class {
                public function getNettoPrice()
                {
                    return 1.999999999;
                }

                public function getBruttoPrice()
                {
                    return 2.139999999;
                }

                public function getVat()
                {
                    return 7.0;
                }

                public function getPrice()
                {
                    return 1.999999999;
                }
            };

            $basketItem1 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem1->method('getTitle')->willReturn('A');
            $basketItem1->method('getAmount')->willReturn(9);
            $basketItem1->method('getUnitPrice')->willReturn($unitPrice1);
            $articleA = $this->createMock(Article::class);
            $articleA->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem1->method('getArticle')->willReturn($articleA);
            $basketItem2 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem2->method('getTitle')->willReturn('B');
            $basketItem2->method('getAmount')->willReturn(3);
            $basketItem2->method('getUnitPrice')->willReturn($unitPrice2);
            $articleB = $this->createMock(Article::class);
            $articleB->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem2->method('getArticle')->willReturn($articleB);
            $basket->method('getContents')->willReturn([$basketItem1, $basketItem2]);

            // Expected parts:
            // item_total: 0.123456789*9 + 1.999999999*3 = 7.111111098 -> 7.11
            // tax_total: 9*(0.123456789*0.19 -> 0.02345683 -> 0.02) + 3*(1.999999999*0.07 -> 0.13999999993 -> 0.14) = 0.18 + 0.42 = 0.60
            // shipping: 0.01, discount: 0.00; amount = 7.11 + 0.60 + 0.01 - 0.00 = 7.72
            $expectedAmount = 7.72;
            $priceStub = new class ($expectedAmount) {
                private float $v;

                public function __construct($v)
                {
                    $this->v = $v;
                }

                public function getBruttoPrice()
                {
                    return $this->v;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            // Map items and build amount
            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            /** @var AmountWithBreakdown $amount */
            $amount = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);

            $this->assertSame('7.11', $amount->breakdown->item_total->value);
            $this->assertSame('0.60', $amount->breakdown->tax_total->value);
            $this->assertSame('0.01', $amount->breakdown->shipping->value);
            $this->assertSame('0.00', $amount->breakdown->discount->value);
            $this->assertSame('7.72', $amount->value);
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testAmountValueEqualsBreakdownSumWhenRoundingErrorsOccurAcrossItems(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force net mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', true);

        try {
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR','decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.005); // -> 0.01
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.014); // -> 0.01

            // Choose values prone to rounding differences
            $unitPrice1 = new class {
                public function getNettoPrice()
                {
                    return 0.3333333;
                }

                public function getBruttoPrice()
                {
                    return 0.3966666;
                }

                public function getVat()
                {
                    return 19.0;
                }

                public function getPrice()
                {
                    return 0.3333333;
                }
            };
            $unitPrice2 = new class {
                public function getNettoPrice()
                {
                    return 0.6666667;
                }

                public function getBruttoPrice()
                {
                    return 0.7133333;
                }

                public function getVat()
                {
                    return 7.0;
                }

                public function getPrice()
                {
                    return 0.6666667;
                }
            };

            $basketItem1 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem1->method('getTitle')->willReturn('X');
            $basketItem1->method('getAmount')->willReturn(3);
            $basketItem1->method('getUnitPrice')->willReturn($unitPrice1);
            $article1 = $this->createMock(Article::class);
            $article1->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem1->method('getArticle')->willReturn($article1);
            $basketItem2 = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
            $basketItem2->method('getTitle')->willReturn('Y');
            $basketItem2->method('getAmount')->willReturn(3);
            $basketItem2->method('getUnitPrice')->willReturn($unitPrice2);
            $article2 = $this->createMock(Article::class);
            $article2->method('isVirtualPayPalArticle')->willReturn(false);
            $basketItem2->method('getArticle')->willReturn($article2);
            $basket->method('getContents')->willReturn([$basketItem1, $basketItem2]);

            // Expected: item_total = (0.3333333*3 + 0.6666667*3) = 3.0 -> 3.00
            // tax_total = 3*(0.3333333*19% -> 0.063333327 -> 0.06) + 3*(0.6666667*7% -> 0.046666669 -> 0.05) = 0.18 + 0.15 = 0.33
            // shipping 0.01, discount 0.01 => amount = 3.00 + 0.33 + 0.01 - 0.01 = 3.33
            $expectedAmount = 3.33;
            $priceStub = new class ($expectedAmount) {
                private float $v;

                public function __construct($v)
                {
                    $this->v = $v;
                }

                public function getBruttoPrice()
                {
                    return $this->v;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            /** @var AmountWithBreakdown $amount */
            $amount = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);

            $this->assertSame('3.00', $amount->breakdown->item_total->value);
            $this->assertSame('0.33', $amount->breakdown->tax_total->value);
            $this->assertSame('0.01', $amount->breakdown->shipping->value);
            $this->assertSame('0.01', $amount->breakdown->discount->value);
            $this->assertSame('3.33', $amount->value);
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testGrossModeBasketFromIssueDataProducesExpectedTwoDecimalResults(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force gross mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', false);

        try {
            $basketMock = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR','decimal' => 2];
            $basketMock->method('getBasketCurrency')->willReturn($currency);
            $basketMock->method('getPayPalCheckoutDeliveryCosts')->willReturn(0.0);
            $basketMock->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
            // Total gross from issue: 244.52679 -> PayPal money rounds to 244.53
            $priceMockTotal = $this->createMock(\OxidEsales\Eshop\Core\Price::class);
            $priceMockTotal->method('getBruttoPrice')->willReturn(244.52679);
            $basketMock->method('getPrice')->willReturn($priceMockTotal);

            // UnitPrice mocks: use oxprice-like mocks as in PatchRequestFactoryTest
            $makeUnitPrice = function (float $gross) {
                $price = $this->getMockBuilder(\OxidEsales\Eshop\Core\Price::class)
                    ->onlyMethods(['getPrice', 'getBruttoPrice'])
                    ->getMock();
                $price->expects($this->any())->method('getPrice')->willReturn($gross);
                $price->expects($this->any())->method('getBruttoPrice')->willReturn($gross);
                return $price;
            };

            $unit1 = $makeUnitPrice(29.93445); // qty 3 => 89.80335 gross
            $unit2 = $makeUnitPrice(129.76789); // qty 1 => 129.76789 gross
            $unit3 = $makeUnitPrice(24.95555); // qty 1 => 24.95555 gross

            // BasketItem mocks
            $makeBasketItem = function (string $title, int $qty, $unitPrice) {
                $basketItem = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
                $basketItem->method('getTitle')->willReturn($title);
                $basketItem->method('getArticle')->willReturn(oxNew(Article::class));
                $basketItem->method('getAmount')->willReturn($qty);
                $basketItem->method('getUnitPrice')->willReturn($unitPrice);
                return $basketItem;
            };

            $item1 = $makeBasketItem('Kuyichi Ledergürtel JEVER', 3, $unit1);
            $item2 = $makeBasketItem('Trapez ION SOL KITE 2011', 1, $unit2);
            $item3 = $makeBasketItem('Transportcontainer THE BARREL', 1, $unit3);
            $basketMock->method('getContents')->willReturn([$item1, $item2, $item3]);

            // Build amount (gross mode => tax_total should be 0.00; item_total sums gross unit*qty, rounded to 2 decimals)
            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basketMock, []]);
            /** @var AmountWithBreakdown $amount */
            $amount = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);
            Registry::getSession()->setBasket($basketMock);
            $factory->setBasket($basketMock);
            $purchaseUnitRequests = $factory->getPurchaseUnits(null, null, true);
            // Additionally compute per-item sum using mapItems (rounded per-unit values times quantity)
            $itemsArr = $purchaseUnitRequests[0]->items;
            $sumItems = 0.0;

            foreach ($itemsArr as $it) {
                $unit = isset($it->unit_amount) ? (float)$it->unit_amount->value : 0.0;
                $qty = isset($it->quantity) ? (float)$it->quantity : 1.0;
                $sumItems += $unit * $qty;
            }
            $sumItemsStr = number_format($sumItems, 2, '.', '');

            $this->assertSame('EUR', $amount->currency_code);
            $this->assertSame('244.53', $amount->breakdown->item_total->value, 'item_total should round to 244.53');
            $this->assertSame('0.00', $amount->breakdown->tax_total->value, 'tax_total should be 0.00 in gross mode');
            $this->assertSame('244.53', $amount->value, 'amount value should equal rounded gross total');
            $this->assertEquals(244.53, (float)$sumItemsStr, 'sum of rounded unit amounts should be 244.53');
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }

    public function testNetModeBasketFromIssueDataProducesExpectedTwoDecimalResults(): void
    {
        $factory = $this->makeFactory();

        // Set Config to force net mode using existing Config instance
        $config = Registry::getConfig();
        $prevShowNetPrice = $config->getConfigParam('blShowNetPrice');
        $config->setConfigParam('blShowNetPrice', true);

        try {
            $basket = $this->getMockBuilder(Basket::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getBasketCurrency','getContents','getPrice','getPayPalCheckoutDeliveryCosts','getPayPalCheckoutDiscountBrutto'])
                ->getMock();
            $currency = (object)['name' => 'EUR','decimal' => 2];
            $basket->method('getBasketCurrency')->willReturn($currency);
            $basket->method('getPayPalCheckoutDeliveryCosts')->willReturn(3.5);
            $basket->method('getPayPalCheckoutDiscountBrutto')->willReturn(0.0);
            // Final gross from issue: 412.24572 -> PayPal money rounds to 412.25
            $priceStub = new class {
                public function getBruttoPrice()
                {
                    return 412.24572;
                }
            };
            $basket->method('getPrice')->willReturn($priceStub);

            // UnitPrice stubs return net price and getPrice() returns net in net mode
            $makeUnitPriceNet = function (float $net) {
                return new class ($net) {
                    private float $n;

                    public function __construct($n)
                    {
                        $this->n = $n;
                    }

                    public function getNettoPrice()
                    {
                        return $this->n;
                    }

                    public function getBruttoPrice()
                    {
                        return $this->n * 1.19;
                    }

                    public function getVat()
                    {
                        return 19.0;
                    }

                    public function getPrice()
                    {
                        return $this->n; // used by buildAmountArray for item_total in net mode
                    }
                };
            };

            $unitA = $makeUnitPriceNet(24.95676); // qty 5
            $unitB = $makeUnitPriceNet(29.90000); // qty 3
            $unitC = $makeUnitPriceNet(129.00000); // qty 1

            // BasketItem stubs must extend real BasketItem for instanceof checks
            $makeBasketItem = function (string $title, int $qty, $unitPrice) {
                $basketItem = $this->createMock(\OxidEsales\Eshop\Application\Model\BasketItem::class);
                $basketItem->method('getTitle')->willReturn($title);
                $basketItem->method('getAmount')->willReturn($qty);
                $basketItem->method('getUnitPrice')->willReturn($unitPrice);
                $article = $this->createMock(Article::class);
                $article->method('isVirtualPayPalArticle')->willReturn(false);
                $basketItem->method('getArticle')->willReturn($article);
                return $basketItem;
            };

            $item1 = $makeBasketItem('Transportcontainer THE BARREL', 5, $unitA);
            $item2 = $makeBasketItem('Kuyichi Ledergürtel JEVER', 3, $unitB);
            $item3 = $makeBasketItem('Trapez ION SOL KITE 2011', 1, $unitC);
            $basket->method('getContents')->willReturn([$item1, $item2, $item3]);

            // Map items and verify per-unit taxes were rounded to 2 decimals
            $itemsArr = $this->callPrivate($factory, 'mapItems', [$basket]);
            // Expected per-unit taxes: 24.95676*0.19=4.7417844->4.74, 29.90*0.19=5.681->5.68, 129*0.19=24.51
            $this->assertSame('4.74', $itemsArr[0]['tax']['value']);
            $this->assertSame('5.68', $itemsArr[1]['tax']['value']);
            $this->assertSame('24.51', $itemsArr[2]['tax']['value']);

            // Build amount with items for tax_total
            $amountArr = $this->callPrivate($factory, 'buildAmountArray', [$basket, $itemsArr]);
            /** @var AmountWithBreakdown $amount */
            $amount = $this->callPrivate($factory, 'mapAmountWithBreakdown', [$amountArr]);

            // Expected breakdown:
            // item_total: (5*24.95676 + 3*29.9 + 1*129.0) = 343.4838 -> 343.48
            // tax_total: 5*4.74 + 3*5.68 + 1*24.51 = 65.25
            // shipping: 3.50; discount: 0.00; amount.value from basket brutto => 412.25
            $this->assertSame('EUR', $amount->currency_code);
            $this->assertSame('343.48', $amount->breakdown->item_total->value, 'item_total should round to 343.48');
            $this->assertSame('65.25', $amount->breakdown->tax_total->value, 'tax_total should be 65.25 in net mode');
            $this->assertSame('3.50', $amount->breakdown->shipping->value, 'shipping should be 3.50');
            $this->assertSame('0.00', $amount->breakdown->discount->value, 'discount should be 0.00');
            $this->assertSame('412.25', $amount->value, 'amount value should equal rounded basket gross total');
        } finally {
            $config->setConfigParam('blShowNetPrice', $prevShowNetPrice);
        }
    }
}
