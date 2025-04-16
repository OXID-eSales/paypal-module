<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Admin\CoreSetting\Section;

use OxidEsales\Codeception\Page\Page;

class StockSettings extends Page
{
    private string $lowStockDefaultMessageOption = 'confbools[blStockLowDefaultMessage]';

    public function checkLowStockMessageOption(): static
    {
        $I = $this->user;
        $I->checkOption($this->lowStockDefaultMessageOption);

        return $this;
    }

    public function uncheckLowStockMessageOption(): static
    {
        $I = $this->user;
        $I->uncheckOption($this->lowStockDefaultMessageOption);

        return $this;
    }

    public function seeLowStockMessageSelected(): static
    {
        $I = $this->user;
        $I->seeCheckboxIsChecked($this->lowStockDefaultMessageOption);

        return $this;
    }

    public function dontSeeLowStockMessageSelected(): static
    {
        $I = $this->user;
        $I->dontSeeCheckboxIsChecked($this->lowStockDefaultMessageOption);

        return $this;
    }
}
