<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Details;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Account\UserListmania;
use OxidEsales\Codeception\Page\Page;

/**
 * Class for product listmania page.
 * @package OxidEsales\Codeception\Page\Details
 */
class ProductListmania extends Page
{
    public $headerTitle = 'h1';

    /**
     * @return UserListmania
     */
    public function createNewList()
    {
        $I = $this->user;
        $I->click(Translator::translate('CLICK_HERE'));
        $I->waitForPageLoad();
        $userListmania = new UserListmania($I);
        $I->see(Translator::translate('PAGE_TITLE_ACCOUNT_RECOMMLIST'), $userListmania->headerTitle);
        return $userListmania;
    }
}
