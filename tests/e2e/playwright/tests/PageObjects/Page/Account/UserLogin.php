<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Page\Page;
use OxidEsales\Codeception\Module\Translation\Translator;

class UserLogin extends Page
{
    public string $URL = '/en/my-account/';
    public string $breadCrumb = '.breadcrumb';
    public string $headerTitle = 'h1';
    public string $userAccountLoginName = '#loginUser';
    public string $userAccountLoginPassword = '#loginPwd';
    public string $userAccountLoginButton = '#loginButton';
    public string $userForgotPasswordLink = '#forgotPasswordLink';

    public function seePageOpened(): self
    {
        $I = $this->user;
        $I->see(Translator::translate('LOGIN'), $this->headerTitle);

        return $this;
    }

    public function seeLoginForm(): self
    {
        $I = $this->user;
        $I->seeElement($this->userAccountLoginName);
        $I->seeElement($this->userAccountLoginPassword);
        $I->seeElement($this->userAccountLoginButton);

        return $this;
    }

    public function login(string $userName, string $userPassword): UserAccount
    {
        $I = $this->user;
        $I->fillField($this->userAccountLoginName, $userName);
        $I->fillField($this->userAccountLoginPassword, $userPassword);
        $I->retryClick($this->userAccountLoginButton);
        $I->dontSee(Translator::translate('LOGIN'));
        return new UserAccount($I);
    }

    public function loginWithError(string $userName, string $userPassword): self
    {
        $I = $this->user;
        $I->fillField($this->userAccountLoginName, $userName);
        $I->fillField($this->userAccountLoginPassword, $userPassword);
        $I->retryClick($this->userAccountLoginButton);
        $I->see(Translator::translate('LOGIN'));
        return $this;
    }

    public function openUserPasswordReminderPage(): UserPasswordReminder
    {
        $I = $this->user;
        $I->click($this->userForgotPasswordLink);
        $userPasswordReminderPage = new UserPasswordReminder($I);
        $userPasswordReminderPage->seeOnBreadCrumb(
            Translator::translate("YOU_ARE_HERE") . ":" . Translator::translate("FORGOT_PASSWORD")
        );
        return $userPasswordReminderPage;
    }
}
