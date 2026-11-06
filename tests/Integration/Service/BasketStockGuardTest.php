<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\BasketStockGuard;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

/**
 * The basket of a customer whose article was bought by someone else meanwhile.
 */
final class BasketStockGuardTest extends BaseTestCase
{
    private const ARTICLE_SOLD_OUT_OFFLINE = '_ppguard_offline';
    private const ARTICLE_SOLD_OUT_NOT_ORDERABLE = '_ppguard_notorderable';
    private const ARTICLE_LOW_STOCK = '_ppguard_lowstock';
    private const ARTICLE_IN_STOCK = '_ppguard_instock';

    /** stock flags of the core */
    private const STOCKFLAG_OFFLINE_IF_SOLD_OUT = 2;
    private const STOCKFLAG_NOT_ORDERABLE_IF_SOLD_OUT = 3;

    protected function setUp(): void
    {
        parent::setUp();

        Registry::getConfig()->setConfigParam('blUseStock', true);
        Registry::getConfig()->setConfigParam('blPsBasketReservationEnabled', false);
        Registry::getSession()->deleteVariable('Errors');

        $this->createArticle(self::ARTICLE_SOLD_OUT_OFFLINE, 'Guard offline', self::STOCKFLAG_OFFLINE_IF_SOLD_OUT);
        $this->createArticle(
            self::ARTICLE_SOLD_OUT_NOT_ORDERABLE,
            'Guard not orderable',
            self::STOCKFLAG_NOT_ORDERABLE_IF_SOLD_OUT
        );
        $this->createArticle(self::ARTICLE_LOW_STOCK, 'Guard <low> stock', self::STOCKFLAG_OFFLINE_IF_SOLD_OUT);
        $this->createArticle(self::ARTICLE_IN_STOCK, 'Guard in stock', self::STOCKFLAG_OFFLINE_IF_SOLD_OUT);
    }

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxarticles');
        Registry::getSession()->deleteVariable('Errors');
        Registry::getSession()->setBasket(null);

        parent::tearDown();
    }

    public function testBasketMatchingTheStockIsLeftAlone(): void
    {
        $basket = $this->createBasket([self::ARTICLE_IN_STOCK => 2, self::ARTICLE_LOW_STOCK => 1]);

        $this->assertFalse($this->getGuard()->correctBasket($basket));
        $this->assertCount(2, $basket->getContents());
        $this->assertSame([], $this->getDisplayedMessages());
    }

    public function testArticleSoldOutAndSwitchedOfflineIsRemovedWithMessage(): void
    {
        $basket = $this->createBasket([self::ARTICLE_IN_STOCK => 1, self::ARTICLE_SOLD_OUT_OFFLINE => 1]);
        $totalBefore = $basket->getPrice()->getBruttoPrice();

        $this->setStock(self::ARTICLE_SOLD_OUT_OFFLINE, 0);

        $this->assertTrue($this->getGuard()->correctBasket($basket));
        $this->assertSame([self::ARTICLE_IN_STOCK], $this->getProductIds($basket));
        // recalculated: the total no longer contains the removed article
        $this->assertEqualsWithDelta($totalBefore - 10.0, $basket->getPrice()->getBruttoPrice(), 0.001);

        $messages = $this->getDisplayedMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Guard offline', $messages[0]);
    }

    public function testArticleSoldOutAndNotOrderableIsRemoved(): void
    {
        $basket = $this->createBasket([self::ARTICLE_IN_STOCK => 1, self::ARTICLE_SOLD_OUT_NOT_ORDERABLE => 1]);

        $this->setStock(self::ARTICLE_SOLD_OUT_NOT_ORDERABLE, 0);

        $this->assertTrue($this->getGuard()->correctBasket($basket));
        $this->assertSame([self::ARTICLE_IN_STOCK], $this->getProductIds($basket));
        $this->assertCount(1, $this->getDisplayedMessages());
    }

    public function testAmountIsReducedToTheRemainingStock(): void
    {
        $basket = $this->createBasket([self::ARTICLE_LOW_STOCK => 3]);

        $this->setStock(self::ARTICLE_LOW_STOCK, 1);

        $this->assertTrue($this->getGuard()->correctBasket($basket));
        $basketItem = current($basket->getContents());
        $this->assertEquals(1, $basketItem->getAmount());
        $this->assertEqualsWithDelta(10.0, $basket->getPrice()->getBruttoPrice(), 0.001);

        $messages = $this->getDisplayedMessages();
        $this->assertCount(1, $messages);
        // the title is merchant content and printed unescaped by the themes
        $this->assertStringContainsString('Guard &lt;low&gt; stock', $messages[0]);
        $this->assertStringContainsString('1', $messages[0]);
    }

    public function testSecondCheckFindsNothingToCorrect(): void
    {
        $basket = $this->createBasket([self::ARTICLE_IN_STOCK => 1, self::ARTICLE_SOLD_OUT_OFFLINE => 1]);
        $this->setStock(self::ARTICLE_SOLD_OUT_OFFLINE, 0);

        $this->assertTrue($this->getGuard()->correctBasket($basket));
        // no cascade: the corrected basket passes the next check (e.g. on the reloaded page)
        $this->assertFalse($this->getGuard()->correctBasket($basket));
        $this->assertCount(1, $this->getDisplayedMessages());
    }

    /**
     * The stock check inside finalizeOrder() is the last net. OXID 6's
     * OrderController::execute() shows its exception, so the exception names the article.
     *
     * @dataProvider unavailableArticleProvider
     */
    public function testValidateStockNamesTheRemovedArticleInItsException(string $articleId): void
    {
        $basket = $this->createBasket([self::ARTICLE_IN_STOCK => 1, $articleId => 1]);
        $this->setStock($articleId, 0);

        try {
            oxNew(Order::class)->validateStock($basket);
            $this->fail('validateStock() accepted an article that is no longer available');
        } catch (NoArticleException | ArticleInputException $exception) {
            $this->assertStringContainsString(
                $articleId === self::ARTICLE_SOLD_OUT_OFFLINE ? 'Guard offline' : 'Guard not orderable',
                $exception->getMessage()
            );
        }

        $this->assertSame([self::ARTICLE_IN_STOCK], $this->getProductIds($basket));
        // shown by the caller (core execute() or OrderManager), not a second time here
        $this->assertSame([], $this->getDisplayedMessages());
    }

    public function unavailableArticleProvider(): array
    {
        return [
            'sold out, offline' => [self::ARTICLE_SOLD_OUT_OFFLINE],
            'sold out, not orderable' => [self::ARTICLE_SOLD_OUT_NOT_ORDERABLE],
        ];
    }

    private function getGuard(): BasketStockGuard
    {
        return $this->getServiceFromContainer(BasketStockGuard::class);
    }

    private function createArticle(string $id, string $title, int $stockFlag): void
    {
        $article = oxNew(Article::class);
        $article->setId($id);
        $article->assign([
            'oxshopid' => Registry::getConfig()->getShopId(),
            'oxactive' => 1,
            'oxartnum' => $id,
            'oxtitle' => $title,
            'oxprice' => 10.0,
            'oxstock' => 5,
            'oxstockflag' => $stockFlag,
        ]);
        $article->save();
    }

    private function setStock(string $id, int $stock): void
    {
        DatabaseProvider::getDb()->execute(
            'update oxarticles set oxstock = ? where oxid = ?',
            [$stock, $id]
        );
    }

    /**
     * @param array<string, int> $amounts article id => amount
     */
    private function createBasket(array $amounts): Basket
    {
        $basket = oxNew(Basket::class);
        // the core's BasketItem::setAmount() counts the stock in the session basket
        Registry::getSession()->setBasket($basket);
        foreach ($amounts as $articleId => $amount) {
            $basket->addToBasket($articleId, $amount);
        }
        $basket->calculateBasket(true);

        return $basket;
    }

    /**
     * @return string[]
     */
    private function getProductIds(Basket $basket): array
    {
        $ids = [];
        foreach ($basket->getContents() as $basketItem) {
            $ids[] = $basketItem->getProductId();
        }

        return $ids;
    }

    /**
     * @return string[]
     */
    private function getDisplayedMessages(): array
    {
        $errors = Registry::getSession()->getVariable('Errors');
        $messages = [];
        foreach ((array) ($errors['default'] ?? []) as $serializedError) {
            $messages[] = unserialize($serializedError)->getOxMessage();
        }

        return $messages;
    }
}
