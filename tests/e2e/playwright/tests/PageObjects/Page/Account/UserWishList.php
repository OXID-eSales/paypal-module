<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Component\Header\MiniBasket;
use OxidEsales\Codeception\Page\Page;
use OxidEsales\Codeception\Page\Details\ProductDetails;

class UserWishList extends Page
{
    use MiniBasket;
    use AccountMenu;

    public string $URL = '/en/my-wish-list/';
    public string $breadCrumb = '.breadcrumb';
    public $headerTitle = 'h1';
    public string $productTitle = '//div[@id="noticelistProductList"]/div/div[%s]//a';
    public string $productDescription = '//div[@id="noticelistProductList"]/div/div[%s]//div[@class="card-text"]';
    public string $productPrice = '#productPrice_noticelistProductList_%s';
    public string $basketAmount = '#amountToBasket_noticelistProductList_%s';
    public string $toBasketButton = '#toBasket_noticelistProductList_%s';
    public string $removeButton = '//button[@data-triggerform="remove_tonoticelistnoticelistProductList_%s"]';

    public function seePageOpen(): self
    {
        $this->user->see(Translator::translate('PAGE_TITLE_ACCOUNT_NOTICELIST'), $this->headerTitle);
        return $this;
    }

    /**
     * Checks if given product data is shown correctly:
     * ['title', 'description', 'price']
     *
     * @param array $productData
     * @param int   $itemPosition
     *
     * @return $this
     */
    public function seeProductData(array $productData, int $itemPosition = 1)
    {
        $I = $this->user;
        $I->see($productData['title'], sprintf($this->productTitle, $itemPosition));
        $I->see($productData['description'], sprintf($this->productDescription, $itemPosition));
        $I->see($productData['price'], sprintf($this->productPrice, $itemPosition));
        return $this;
    }

    /**
     * Opens the details page of the selected product.
     *
     * @param int $itemPosition
     *
     * @return ProductDetails
     */
    public function openProductDetailsPage(int $itemPosition)
    {
        $I = $this->user;
        $I->click(sprintf($this->productTitle, $itemPosition));
        return new ProductDetails($I);
    }

    /**
     * Adds selected product to the basket.
     *
     * @param int $itemPosition
     * @param int $amount
     *
     * @return $this
     */
    public function addProductToBasket(int $itemPosition, int $amount)
    {
        $I = $this->user;
        $I->fillField(sprintf($this->basketAmount, $itemPosition), $amount);
        $I->click(sprintf($this->toBasketButton, $itemPosition));
        $I->waitForPageLoad();
        return $this;
    }

    /**
     * Removes selected product from the list.
     *
     * @param int $itemPosition
     *
     * @return $this
     */
    public function removeProductFromList(int $itemPosition)
    {
        $I = $this->user;
        $I->click(sprintf($this->removeButton, $itemPosition));
        $I->waitForPageLoad();
        return $this;
    }
}
