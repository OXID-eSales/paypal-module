<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

trait LanguageMenu
{
    public string $languageMenuButton = '//div[@class="meta"]//div[contains(@class,"dropdowns")]/button';

    public string $openLanguageMenu = '//div[@class="meta"]//div[contains(@class,"dropdown-menu")]/form/div/button';

    /**
     * @param string $language
     *
     * @return $this
     */
    public function switchLanguage(string $language)
    {
        $I = $this->user;
        $I->click($this->languageMenuButton);
        $I->waitForElement($this->openLanguageMenu);
        $I->click($this->openLanguageMenu);
        $I->click($language);
        $I->waitForElementNotVisible($this->openLanguageMenu);
        return $this;
    }
}
