<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Component\UserForm;
use OxidEsales\Codeception\Page\Page;

class UserRegistration extends Page
{
    use UserForm;
    use AccountMenu;

    public string $URL = '/en/open-account';
    public string $breadCrumb = '.breadcrumb';
    public $headerTitle = 'h1';
    public string $saveFormButton = '#accUserSaveTop';

    public function seePageOpen(): self
    {
        $this->user->see(Translator::translate('OPEN_ACCOUNT'), $this->headerTitle);
        return $this;
    }

    public function registerUser(): self
    {
        $I = $this->user;
        $I->retryClick($this->saveFormButton);
        $I->see(Translator::translate('MESSAGE_WELCOME_REGISTERED_USER'), $this->headerTitle);
        return $this;
    }
}
