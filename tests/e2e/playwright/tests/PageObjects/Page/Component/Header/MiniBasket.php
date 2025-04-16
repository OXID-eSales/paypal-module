<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

use OxidEsales\Codeception\Module\Context;
use OxidEsales\Codeception\Page\Checkout\Basket;
use OxidEsales\Codeception\Page\Checkout\PaymentCheckout;
use OxidEsales\Codeception\Page\Checkout\UserCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;

trait MiniBasket
{
    public string $miniBasketMenuElement = '//button[contains(@class,"btn-minibasket")]';
    public string $miniBasketTitle = '#basketModalLabel';
    public string $miniBasketItemTitle = '//div[@class="minibasket-items"]/div[%d]/a/span[2]';
    public string $miniBasketItemAmount = '//div[@class="minibasket-items"]/div[%d]/a/span[2]';
    public string $miniBasketItemPrice = '//div[@class="minibasket-items"]/div[%d]/a/span[2]';
    public string $miniBasketSummaryPrice = '//div[contains(@class,"minibasket-total-row")]/div[2]';
    public string $miniBasketItemsSummaryPrice = '//div[contains(@class,"col-4 text-end")]';
    public string $miniBasketCountDown = '#countdown';
    public string $miniBasketClose = '//div[@id="basketModal"]//button';
    private string $itemCountBadge = '//button[@class="btn btn-minibasket"]//span[@class="badge"]';
    private string $addToWishlist = '//*[@id="list_cartItem_%d"]/div[2]/div[1]/div[3]/div/div[1]/div/div[2]/button[2]';

    /**
     * $basketProducts[] = ['title' => productTitle,
     *                   'price' => productPrice,
     *                   'amount' => productAmount,]
     */
    public function seeMiniBasketContains(array $basketProducts, string $basketSummaryPrice, string $totalAmount): self
    {
        $I = $this->user;
        $this->openMiniBasket();
        $I->see(sprintf('%s %s', $totalAmount, Translator::translate('ITEMS_IN_BASKET')));
        foreach ($basketProducts as $key => $basketProduct) {
            $itemPosition = (string)++$key;
            $I->see($basketProduct['title'], $I->clearString(sprintf($this->miniBasketItemTitle, $itemPosition)));
            $I->see((string)($basketProduct['amount']), sprintf($this->miniBasketItemAmount, $itemPosition));
            $I->see((string)$basketProduct['price'], sprintf($this->miniBasketItemPrice, $itemPosition));
        }
        $I->see($basketSummaryPrice, $this->miniBasketItemsSummaryPrice);
        return $this;
    }

    public function openMiniBasket(): self
    {
        $I = $this->user;
        $I->waitForPageLoad();
        $I->waitForElementClickable($this->miniBasketMenuElement);
        $I->click($this->miniBasketMenuElement);
        $I->waitForPageLoad();
        $I->waitForElementVisible($this->miniBasketTitle);
        return $this;
    }

    public function closeMiniBasket(): self
    {
        $I = $this->user;
        $I->waitForElementClickable($this->miniBasketClose);
        $I->wait(1);
        $I->retryClick($this->miniBasketClose);
        $I->waitForElementNotVisible($this->miniBasketTitle);
        return $this;
    }

    public function openCheckout(): UserCheckout|PaymentCheckout
    {
        $I = $this->user;
        $I->waitForText(Translator::translate('CHECKOUT'));
        $I->retryClick(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        if (Context::isUserLoggedIn()) {
            return new PaymentCheckout($I);
        }

        return new UserCheckout($I);
    }

    public function openBasketDisplay(): Basket
    {
        $I = $this->user;
        $I->retryClick(Translator::translate('DISPLAY_BASKET'));
        $I->see(Translator::translate('CART'));
        return new Basket($I);
    }

    public function checkBasketEmpty(): self
    {
        $I = $this->user;
        $this->openMiniBasket();
        $I->see(Translator::translate('BASKET_EMPTY'));
        $this->closeMiniBasket();
        return $this;
    }

    public function seeCountdownWithinBasket(): self
    {
        $I = $this->user;
        $this->openMiniBasket();
        $I->waitForElementVisible($this->miniBasketCountDown);
        $this->closeMiniBasket();
        return $this;
    }

    public function seeItemCountBadge(string $itemCount): self
    {
        $I = $this->user;
        $I->see($itemCount, $this->itemCountBadge);

        return $this;
    }

    public function dontSeeItemCountBadge(): self
    {
        $I = $this->user;
        $I->dontSeeElement($this->itemCountBadge);

        return $this;
    }

    public function addProductToTheWishlist(int $productPosition): self
    {
        $I = $this->user;
        $I->waitForElementClickable(sprintf($this->addToWishlist, $productPosition));
        $I->click(sprintf($this->addToWishlist, $productPosition));
        return $this;
    }

    public function seeAddToTheWishlistStar(int $productPosition): self
    {
        $I = $this->user;
        $I->seeElement(sprintf($this->addToWishlist, $productPosition));
        return $this;
    }

    public function dontSeeAddToWishlistStar(int $productPosition): self
    {
        $I = $this->user;
        $I->dontSeeElement(sprintf($this->addToWishlist, $productPosition));
        return $this;
    }
}
