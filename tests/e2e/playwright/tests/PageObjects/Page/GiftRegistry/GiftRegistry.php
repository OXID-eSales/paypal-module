<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\GiftRegistry;

use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Page;

/**
 * Class for gift-registry page.
 * @package OxidEsales\Codeception\Page\GiftRegistry
 */
class GiftRegistry extends Page
{
    use AccountMenu;

    // include url of current page
    public string $URL = '/en/gift-registry/';

    // include bread crumb of current page
    public string $breadCrumb = '.breadcrumb';

    public $headerTitle = 'h1';

    public $productTitle = '//div[@id="wishlistProductList"]/div/div[%s]/div/div[2]/div/div/div/a';

    public $productDescription = '//div[@id="wishlistProductList"]/div/div[%s]/div/div[2]/div/div/div/div';

    public $productPrice = '#productPrice_wishlistProductList_%s';

    /**
     * Check if product data is displayed correctly.
     * $productData = ['title', 'description', 'price']
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
}
