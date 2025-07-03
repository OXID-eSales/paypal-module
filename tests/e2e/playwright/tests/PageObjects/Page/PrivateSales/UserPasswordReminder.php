<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\PrivateSales;

use OxidEsales\Codeception\Page\Page;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * Class for forgot-password page
 * @package OxidEsales\Codeception\Page\PrivateSales
 */
class UserPasswordReminder extends Page
{
    // include url of current page
    public string $URL = '/en/forgot-password/';

    // include bread crumb of current page
    public string $breadCrumb = '.breadcrumb';

    public $forgotPasswordUserEmail = '#forgotPasswordUserLoginName';

    public $resetPasswordButton = '';

    public $backToShop = '#backToShop';

    /**
     * @param string $userEmail
     *
     * @return $this
     */
    public function resetPassword(string $userEmail)
    {
        $I = $this->user;
        $I->fillField($this->forgotPasswordUserEmail, $userEmail);
        $I->click(Translator::translate('REQUEST_PASSWORD'));
        return $this;
    }

    /**
     * @return Login
     */
    public function goBackToShop()
    {
        $I = $this->user;
        $I->click($this->backToShop);
        return new Login($I);
    }
}
