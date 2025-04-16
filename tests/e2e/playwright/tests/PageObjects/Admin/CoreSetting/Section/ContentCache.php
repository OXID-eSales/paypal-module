<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Admin\CoreSetting\Section;

use OxidEsales\Codeception\Page\Page;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceTester;

class ContentCache extends Page
{
    private string $contentCacheEnableCheckbox = '//input[@name="enable_content_cache" and @type="checkbox"]';
    private string $contentCacheSaveButton = '//form[@id=\'myedit1\']//div[@class="groupExp"][2]//input[@name="save"]';

    public function enableContentCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->checkOption($this->contentCacheEnableCheckbox);

        return $this;
    }

    public function disableContentCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->uncheckOption($this->contentCacheEnableCheckbox);

        return $this;
    }

    public function seeContentCacheEnabled(): static
    {
        $I = $this->user;

        $I->seeCheckboxIsChecked($this->contentCacheEnableCheckbox);

        return $this;
    }

    public function seeContentCacheDisabled(): static
    {
        $I = $this->user;

        $I->dontSeeCheckboxIsChecked($this->contentCacheEnableCheckbox);

        return $this;
    }

    public function saveContentCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->click($this->contentCacheSaveButton);
        $I->waitForPageLoad();

        return $this;
    }
}
