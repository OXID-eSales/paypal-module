<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Lists;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Page;

/**
 * @package OxidEsales\Codeception\Page\Lists
 */
class ManufacturerList extends Page
{
    public string $URL = '/en/By-manufacturer/';

    public string $headerTitle = 'h1';

    public string $listItemTitle = '//div[contains(@class,"cat-list")]/a[%s]/span';

    public string $listItemCount = '//div[contains(@class,"cat-list")]/a[%s]/span';

    public string $listItemLink = '//div[contains(@class,"cat-list")]/a[%s]';

    /**
     * Check if item data is displayed correctly.
     * $itemData = ['title', 'count']
     *
     * @param array $itemData
     * @param int   $itemId   The position of the item in the list.
     *
     * @return $this
     */
    public function seeManufacturerData(array $itemData, int $itemId = 1): self
    {
        $I = $this->user;
        $I->see($itemData['title'], sprintf($this->listItemTitle, $itemId));
        $I->see($itemData['count'], sprintf($this->listItemCount, $itemId));
        return $this;
    }

    /**
     * @param int $itemId The position of the item in the list.
     *
     * @return ProductList
     */
    public function openManufacturerPage(int $itemId): ProductList
    {
        $I = $this->user;
        $productListPage = new ProductList($I);
        $I->click(sprintf($this->listItemLink, $itemId));
        $I->waitForPageLoad();
        return $productListPage;
    }
}
