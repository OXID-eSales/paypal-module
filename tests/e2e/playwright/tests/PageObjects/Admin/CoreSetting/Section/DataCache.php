<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Admin\CoreSetting\Section;

use OxidEsales\Codeception\Page\Page;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceTester;

class DataCache extends Page
{
    private string $dataCacheEnableCheckbox = '//input[@name="enable_data_cache" and @type="checkbox"]';
    private string $dataCacheSaveButton = '//form[@id=\'myedit1\']//div[@class="groupExp"][1]/'
    . '/input[@name="save"]';

    public function enableDataCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->checkOption($this->dataCacheEnableCheckbox);

        return $this;
    }

    public function disableDataCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->uncheckOption($this->dataCacheEnableCheckbox);

        return $this;
    }

    public function seeDataCacheEnabled(): static
    {
        $I = $this->user;

        $I->seeCheckboxIsChecked($this->dataCacheEnableCheckbox);

        return $this;
    }

    public function seeDataCacheDisabled(): static
    {
        $I = $this->user;

        $I->dontSeeCheckboxIsChecked($this->dataCacheEnableCheckbox);

        return $this;
    }

    public function saveDataCache(): static
    {
        $I = $this->user;

        $I->selectEditFrame();
        $I->click($this->dataCacheSaveButton);
        $I->waitForPageLoad();

        return $this;
    }
}
