<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\PrivateSales;

use OxidEsales\Codeception\Page\Account\UserAccount;
use OxidEsales\Codeception\Page\Page;

class Login extends Page
{
    public string $URL = '/';

    // include bread crumb of current page
    public string $breadCrumb = '.breadcrumb';

    public $headerTitle = 'h1';

    public $forgotPassword = '#forgotPasswordLink';

    public $userAccountLoginName = '#loginUser';

    public $userAccountLoginPassword = '#loginPwd';

    public $userAccountLoginButton = '#loginButton';

    public $userForgotPasswordLink = '#forgotPasswordLink';

    public $confirmAGBOption = 'ord_agb';

    public $confirmAGBButton = '//form[@id="private-sales-login"]//button';

    public $userRegistration = '#openAccountLink';

    public function login(string $userName, string $userPassword)
    {
        $I = $this->user;
        $I->fillField($this->userAccountLoginName, $userName);
        $I->fillField($this->userAccountLoginPassword, $userPassword);
        $I->click($this->userAccountLoginButton);
        $I->waitForPageLoad();
        return $this;
    }

    public function openUserPasswordReminderPage()
    {
        $I = $this->user;
        $I->retryClick($this->forgotPassword);
        $I->waitForPageLoad();
        return new UserPasswordReminder($I);
    }

    public function confirmAGB()
    {
        $I = $this->user;
        $I->checkOption($this->confirmAGBOption);
        $I->click($this->confirmAGBButton);
        $I->waitForPageLoad();
        return new UserAccount($I);
    }

    public function openRegistrationPage()
    {
        $I = $this->user;
        $I->click($this->userRegistration);
        $I->waitForPageLoad();
        return new Registration($I);
    }
}
