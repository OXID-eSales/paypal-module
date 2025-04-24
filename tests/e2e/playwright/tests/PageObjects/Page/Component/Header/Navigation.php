<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Page\Lists\ProductList;

/**
 * Trait for the navigation widget in the header.
 * @package OxidEsales\Codeception\Page\Component\Header
 */
trait Navigation
{
    public $homeLink = '//a[contains(@class,"logo-link")]';

    /**
     * @return Home
     */
    public function openHomePage()
    {
        $I = $this->user;
        $I->click($this->homeLink);
        $I->waitForPageLoad();
        return new Home($I);
    }

    /**
     * Open selected category page.
     *
     * @param string $category
     *
     * @return ProductList
     */
    public function openCategoryPage(string $category)
    {
        $I = $this->user;
        $I->retryClick(['link' => $category]);
        return new ProductList($I);
    }
}
