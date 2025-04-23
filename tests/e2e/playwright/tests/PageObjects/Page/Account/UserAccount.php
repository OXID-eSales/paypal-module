<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Account\Component\AccountNavigation;
use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Page;

class UserAccount extends Page
{
    use AccountMenu;
    use AccountNavigation;

    public string $URL = '/en/my-account/';
    public string $headerTitle = '';
    public string $dashboardChangePasswordPanelHeader = '#linkAccountPassword';
    public string $dashboardCompareListPanelHeader = '//div[@class="accountDashboardView"]/div/div[2]/div[3]/div[1]';
    public string $dashboardCompareListPanelContent = '//div[@class="accountDashboardView"]/div/div[2]/div[3]/div[2]';
    public string $dashboardWishListPanelHeader = '//div[@class="accountDashboardView"]/div/div[2]/div[1]/div[1]';
    public string $dashboardWishListPanelContent = '//div[@class="accountDashboardView"]/div/div[2]/div[1]/div[2]';
    public string $dashboardGiftRegistryPanelHeader = '//h2';
    public string $dashboardGiftRegistryPanelContent = '//h2[contains(text(),"%s")]/following-sibling::div';
    public string $dashboardListmaniaPanelHeader = '//div[@class="accountDashboardView"]/div/div[2]/div[4]/div[1]';
    public string $dashboardListmaniaPanelContent = '//div[@class="accountDashboardView"]/div/div[2]/div[4]/div[2]';
    public string $dashboardOrderHistoryHeader = '//h2[contains(text(),"%s")]/following-sibling::div';
    public string $openReviewPageOnDashboard = '//div[contains(text(),"%s")]/following-sibling::a';

    public function seePageOpened(): self
    {
        $this->user->see(Translator::translate('LOGOUT'));
        return $this;
    }

    public function seeUserAccount(array $userData): self
    {
        $this->user->see(Translator::translate('HELLO') . ' ' . $userData['userName']);
        return $this;
    }

    public function logoutUserInAccountPage(): UserLogin
    {
        $I = $this->user;
        $this->openAccountMenu();
        $I->click(Translator::translate('LOGOUT'));
        $userLoginPage = new UserLogin($I);
        $I->see(Translator::translate('LOGIN'));

        return $userLoginPage;
    }

    public function openChangePasswordPage(): UserChangePassword
    {
        return $this->openChangePasswordPageInAccountMenu();
    }

    public function openOrderHistory(): UserOrderHistory
    {
        $I = $this->user;
        $I->click(
            sprintf($this->dashboardOrderHistoryHeader, Translator::translate('ORDER_HISTORY'))
        );
        $I->waitForPageLoad();
        $userOrderHistoryPage = new UserOrderHistory($I);
        $userOrderHistoryPage->seePageOpened();

        return $userOrderHistoryPage;
    }

    public function seeItemNumberOnGiftRegistryPanel(string $number): self
    {
        $I = $this->user;
        $I->see(Translator::translate('MY_GIFT_REGISTRY'), $this->dashboardGiftRegistryPanelHeader);
        $I->see(
            Translator::translate('PRODUCT') . ' ' . $number,
            sprintf($this->dashboardGiftRegistryPanelContent, Translator::translate('MY_GIFT_REGISTRY'))
        );
        return $this;
    }

    public function seeItemNumberOnReviewPanel(int $number): self
    {
        $this->user->see(Translator::translate('MY_REVIEWS') . ' ' . $number);
        return $this;
    }

    public function openMyReviewsPage(): MyReviews
    {
        $I = $this->user;
        $I->retryClick(sprintf($this->openReviewPageOnDashboard, Translator::translate('MY_REVIEWS')));
        $page = new MyReviews($this->user);
        $this->seePageTitle($page, 'MY_REVIEWS');
        return $page;
    }
}
