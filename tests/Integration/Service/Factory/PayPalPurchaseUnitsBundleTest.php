<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service\Factory;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use ReflectionMethod;

/**
 * The check that refuses an outdated basket must accept bundled articles - also when a module
 * charges for them, as D3 "Schnittkosten" does for cutting costs.
 */
final class PayPalPurchaseUnitsBundleTest extends BaseTestCase
{
    private const ARTICLE_MAIN = '_ppbundle_main';
    private const ARTICLE_SECOND = '_ppbundle_second';
    private const ARTICLE_BUNDLED = '_ppbundle_cut';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createArticle(self::ARTICLE_BUNDLED, 5.0, '');
        $this->createArticle(self::ARTICLE_MAIN, 20.0, self::ARTICLE_BUNDLED);
        $this->createArticle(self::ARTICLE_SECOND, 10.0, '');
    }

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxarticles');
        Registry::getSession()->setBasket(null);

        parent::tearDown();
    }

    public function testCoreBundleWithoutPriceIsAccepted(): void
    {
        $basket = $this->fillBasket(oxNew(Basket::class));

        $this->assertCount(2, $basket->getContents(), 'the core added the bundled article');
        $this->assertEqualsWithDelta(20.0, $basket->getProductsPrice()->getBruttoSum(), 0.001);

        $this->assertBasketIsCalculated($basket);
    }

    public function testBundleChargedByAModuleIsAccepted(): void
    {
        $basket = $this->fillBasket($this->createBasketChargingBundles());

        // the bundled article is part of the products total now
        $this->assertEqualsWithDelta(25.0, $basket->getProductsPrice()->getBruttoSum(), 0.001);

        $this->assertBasketIsCalculated($basket);
    }

    public function testPositionRemovedWithoutRecalculationIsStillRefusedWithChargedBundle(): void
    {
        $basket = $this->fillBasket($this->createBasketChargingBundles(), true);
        foreach ($basket->getContents() as $key => $basketItem) {
            if ($basketItem->getProductId() === self::ARTICLE_SECOND) {
                // as the core's validateStock() does for a sold out article
                $basket->removeItem($key);
            }
        }

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage('The basket positions (25.00) do not match the products total');
        $this->assertBasketIsCalculated($basket);
    }

    /**
     * A basket extension like the one of D3 "Schnittkosten": it prices the bundled articles
     * after the core did, and adds them to the products total.
     */
    private function createBasketChargingBundles(): Basket
    {
        return new class extends Basket {
            // OXID 6 still names it with the underscore
            protected function _calcItemsPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            {
                parent::_calcItemsPrice();

                foreach ($this->_aBasketContents as $basketItem) {
                    if ($basketItem->isBundle() && ($article = $basketItem->getArticle(true))) {
                        $basketItem->setPrice(
                            $article->getBasketPrice($basketItem->getAmount(), $basketItem->getSelList(), $this)
                        );
                        $this->_oProductsPriceList->addToPriceList($basketItem->getPrice());
                        $this->_oDiscountProductsPriceList->addToPriceList($basketItem->getPrice());
                    }
                }
            }
        };
    }

    private function fillBasket(Basket $basket, bool $withSecondArticle = false): Basket
    {
        // the core counts the stock in the session basket
        Registry::getSession()->setBasket($basket);
        $basket->addToBasket(self::ARTICLE_MAIN, 1);
        if ($withSecondArticle) {
            $basket->addToBasket(self::ARTICLE_SECOND, 1);
        }
        $basket->calculateBasket(true);

        return $basket;
    }

    private function assertBasketIsCalculated(Basket $basket): void
    {
        $moduleSettings = $this->createMock(ModuleSettings::class);
        $factory = new PayPalPurchaseUnitsFactory($moduleSettings);
        $factory->setBasket($basket);

        $method = new ReflectionMethod($factory, 'assertBasketIsCalculated');
        $method->setAccessible(true);
        $method->invoke($factory, $basket);
        // reached only if the check accepted the basket
        $this->addToAssertionCount(1);
    }

    private function createArticle(string $id, float $price, string $bundleId): void
    {
        $article = oxNew(Article::class);
        $article->setId($id);
        $article->assign([
            'oxshopid' => Registry::getConfig()->getShopId(),
            'oxactive' => 1,
            'oxartnum' => $id,
            'oxtitle' => $id,
            'oxprice' => $price,
            'oxstock' => 10,
            'oxbundleid' => $bundleId,
        ]);
        $article->save();
    }
}
