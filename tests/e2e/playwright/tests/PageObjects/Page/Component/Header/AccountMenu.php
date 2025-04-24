<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

use OxidEsales\Codeception\Module\Context;
use OxidEsales\Codeception\Page\Account\ProductCompare;
use OxidEsales\Codeception\Page\Account\UserAccount;
use OxidEsales\Codeception\Page\Account\UserGiftRegistry;
use OxidEsales\Codeception\Page\Account\UserLogin;
use OxidEsales\Codeception\Page\Account\UserPasswordReminder;
use OxidEsales\Codeception\Page\Account\UserWishList;
use OxidEsales\Codeception\Page\Account\UserRegistration;
use OxidEsales\Codeception\Module\Translation\Translator;

trait AccountMenu
{
    public string $accountMenuButton = '//div[contains(@class,"menu-dropdowns")]/button';
    public string $openAccountMenuButton = '//div[contains(@class,"menu-dropdowns")]/ul';
    public string $openedAccountMenu = '//div[contains(@class,"menu-dropdowns")]/ul/li';
    public string $userRegistrationLink = '#registerLink';
    public string $userLoginName = '#loginEmail';
    public string $userLoginPassword = '#loginPasword';
    public string $userForgotPasswordButton = '//a[contains(@class,"forgotPasswordOpener")]';
    public string $userLoginButton = '//form[@name="login"]/button';
    public string $userLogoutButton = '';
    public string $badLoginError = '#errorBadLogin';
    public string $userAccount = '//ul[@id="services"]';
    public string $userAccountLink = '//div[contains(@class,"menu-dropdowns")]/ul/li[1]/a';
    public string $userAccountCompareListLink = '//a[@class="dropdown-item"]';
    public string $userAccountWishListLink = '//div[contains(@class,"menu-dropdowns")]/a';
    public string $userAccountGiftRegistryLink = '//a[@class="dropdown-item"]';
    public string $userAccountCompareListText = '//a[@class="dropdown-item"]';
    public string $userAccountWishListText = '//div[contains(@class,"menu-dropdowns")]/a/span';
    public string $userAccountGiftRegistryText = '//ul[@id="services"]/li[4]';

    public function openUserRegistrationPage(): UserRegistration
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible($this->userRegistrationLink);
        $I->click($this->userRegistrationLink);
        $userRegistrationPage = new UserRegistration($I);
        $userRegistrationPage->seePageOpen();
        return $userRegistrationPage;
    }

    public function openUserPasswordReminderPage(): UserPasswordReminder
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible($this->userForgotPasswordButton);
        $I->click($this->userForgotPasswordButton);
        $userPasswordReminderPage = new UserPasswordReminder($I);
        $userPasswordReminderPage->seePageOpen();
        return $userPasswordReminderPage;
    }

    public function loginUser(string $userName, string $userPassword): self
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForText(Translator::translate('FORGOT_PASSWORD'));
        $I->waitForElementVisible($this->userLoginName);
        $I->retryFillField($this->userLoginName, $userName);
        $I->retryFillField($this->userLoginPassword, $userPassword);
        $I->retryClick($this->userLoginButton);
        $I->waitForPageLoad();
        Context::setActiveUser($userName);
        return $this;
    }

    public function logoutUser(): self
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForText(Translator::translate('MY_ACCOUNT'));
        $I->waitForText(Translator::translate('LOGOUT'));
        $I->click(Translator::translate('LOGOUT'));
        $I->waitForPageLoad();
        Context::resetActiveUser();
        return $this;
    }

    public function seeUserLoggedIn(): self
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementNotVisible($this->userRegistrationLink);
        $I->click($this->accountMenuButton);
        return $this;
    }

    public function seeUserLoggedOut(): self
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible($this->userRegistrationLink);
        $I->click($this->accountMenuButton);
        return $this;
    }

    public function openAccountPage(): UserAccount
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible(['link' => Translator::translate('MY_ACCOUNT')]);
        $I->click(['link' => Translator::translate('MY_ACCOUNT')]);
        $I->waitForPageLoad();
        return new UserAccount($I);
    }

    public function openUserGiftRegistryPage(): UserGiftRegistry
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible($this->openedAccountMenu);
        $I->click(Translator::translate('MY_GIFT_REGISTRY'), $this->openAccountMenuButton);
        $I->waitForPageLoad();
        $userGiftRegistryPage = new UserGiftRegistry($I);
        $I->see(Translator::translate('PAGE_TITLE_ACCOUNT_WISHLIST'), $userGiftRegistryPage->headerTitle);
        return $userGiftRegistryPage;
    }

    public function openUserWishListPage(): UserWishList
    {
        $I = $this->user;
        $I->waitForElementVisible($this->userAccountWishListLink);
        $I->click($this->userAccountWishListLink);
        $I->waitForPageLoad();
        $userWishListPage = new UserWishList($I);
        $userWishListPage->seePageOpen();
        return $userWishListPage;
    }

    public function openProductComparePage(): ProductCompare
    {
        return $this->openAccountPage()->openMyCompareListPage();
    }

    public function openUserLoginPage(): UserLogin
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->waitForElementVisible($this->userAccountLink);
        $I->click($this->userAccountLink);
        $I->waitForPageLoad();
        $userLoginPage = new UserLogin($I);
        $I->see(Translator::translate('LOGIN'));
        return $userLoginPage;
    }

    public function openAccountMenu(): self
    {
        $I = $this->user;
        $I->waitForPageLoad();
        $I->waitForElementVisible($this->accountMenuButton);
        $I->click($this->accountMenuButton);
        return $this;
    }

    public function closeAccountMenu(): self
    {
        $I = $this->user;
        $I->waitForElementVisible($this->accountMenuButton);
        $I->click($this->accountMenuButton);
        $I->waitForElementNotVisible($this->openedAccountMenu);
        return $this;
    }

    // will not be implemented for APEX theme
    public function checkCompareListItemCount(int $count): self
    {
        return $this;
    }

    public function checkWishListItemCount(int $count): self
    {
        $I = $this->user;
        if ($count) {
            $I->see((string) $count, $this->userAccountWishListText);
        } else {
            $I->waitForElementNotVisible($this->userAccountWishListText);
        }
        return $this;
    }

    /**
     * Does not exist in APEX theme and should be removed from core in the future
     */
    public function checkGiftRegistryItemCount(int $count): self
    {
        return $this;
    }
}
