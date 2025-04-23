<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

/**
 * Trait for currency menu widget.
 *
 * @package OxidEsales\Codeception\Page\Component\Header
 */
trait CurrencyMenu
{
    public $currencyMenuButton = '//div[@class="meta"]//div[contains(@class,"dropdowns")]/button';

    public $openCurrencyMenu = '//div[@class="meta"]//div[contains(@class,"dropdown-menu")]/form/div[2]/button';


    /**
     * @param string $currency
     *
     * @return $this
     */
    public function switchCurrency(string $currency)
    {
        $I = $this->user;

        $I->click($this->currencyMenuButton);
        $I->waitForElement($this->openCurrencyMenu);
        $I->click($this->openCurrencyMenu);
        $I->click(['link'=>$currency]);
        $I->waitForElementNotVisible($this->openCurrencyMenu);
        return $this;
    }
}
