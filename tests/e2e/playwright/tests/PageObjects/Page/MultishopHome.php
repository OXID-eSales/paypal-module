<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page;

/**
 * Class for home page
 * @package OxidEsales\Codeception\Page
 */
class MultishopHome extends Page
{
    // include url of current page
    public string $URL = '/';

    public $shopLinkPathTemplate = "//div[@id='page']/div[2]//a[%s]";

    public function openShop(int $shopId)
    {
        $I = $this->user;
        $I->click(sprintf($this->shopLinkPathTemplate, $shopId));
        $I->waitForPageLoad();
    }
}
