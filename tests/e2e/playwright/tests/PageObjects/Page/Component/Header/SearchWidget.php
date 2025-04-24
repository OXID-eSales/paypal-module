<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

use OxidEsales\Codeception\Page\Lists\ProductSearchList;

/**
 * Trait for the search widget in the header.
 * @package OxidEsales\Codeception\Page\Component\Header
 */
trait SearchWidget
{
    public $searchField = '#searchParam';

    public $searchButton = '';

    public $searchForm = '//form[name=search]';

    /**
     * Executes the search and opens result page.
     *
     * @param string $value
     *
     * @return ProductSearchList
     */
    public function searchFor(string $value)
    {
        $I = $this->user;
        $I->fillField($this->searchField, $value);
        $I->click('form[name=search] button[type=submit]');
        return new ProductSearchList($I);
    }
}
