<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account\Component;

use Codeception\Util\Locator;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Account\MyDownloads;
use OxidEsales\Codeception\Page\Account\MyReviews;
use OxidEsales\Codeception\Page\Account\NewsletterSettings;
use OxidEsales\Codeception\Page\Account\ProductCompare;
use OxidEsales\Codeception\Page\Account\UserAddress;
use OxidEsales\Codeception\Page\Account\UserChangePassword;
use OxidEsales\Codeception\Page\Account\UserGiftRegistry;
use OxidEsales\Codeception\Page\Account\UserListmania;
use OxidEsales\Codeception\Page\Account\UserWishList;
use OxidEsales\Codeception\Page\Page;

trait AccountNavigation
{
    /** @var string  */
    public $accountMenu = '#dropdownMenuButton';

    public $accountWidget = '//div[@class="dropdown-menu dropdown-menu-end show"]';

    public $userAccountNavigationWishListLink = '//div[contains(@class,"menu-dropdowns")]/a';


    /** @return NewsletterSettings */
    public function openNewsletterSettingsPage(): NewsletterSettings
    {
        $this->clickLinkOnAccountMenu('NEWSLETTER_SETTINGS');
        $page = new NewsletterSettings($this->user);
        $this->seePageTitle($page, 'PAGE_TITLE_ACCOUNT_NEWSLETTER');
        return $page;
    }

    /** @return UserAddress */
    public function openUserAddressPage(): UserAddress
    {
        $this->clickLinkOnAccountMenu('BILLING_SHIPPING_SETTINGS');
        $page = new UserAddress($this->user);
        $this->seePageTitle($page, 'BILLING_SHIPPING_SETTINGS');
        return $page;
    }

    /** @return UserGiftRegistry */
    public function openGiftRegistryPage(): UserGiftRegistry
    {
        $this->clickLinkOnAccountMenu('MY_GIFT_REGISTRY');
        $page = new UserGiftRegistry($this->user);
        $this->seePageTitle($page, 'PAGE_TITLE_ACCOUNT_WISHLIST');
        return $page;
    }

    public function dontSeeGiftRegistryLink(): void
    {
        $this->dontSeeLinkInAccountMenu('MY_GIFT_REGISTRY');
    }

    /**
     * Opens my-wish-list page.
     *
     * @return UserWishList
     */
    public function openWishListPage()
    {
        $I = $this->user;
        $I->waitForElementVisible($this->userAccountWishListLink);
        $I->click($this->userAccountWishListLink);
        $I->waitForPageLoad();
        $userWishListPage = new UserWishList($I);
        $userWishListPage->seePageOpen();
        return $userWishListPage;
    }

    /** @return UserListmania */
    public function openListmaniaPage(): UserListmania
    {
        $this->clickLinkOnAccountMenu('MY_LISTMANIA');
        $page = new UserListmania($this->user);
        $this->seePageTitle($page, 'PAGE_TITLE_ACCOUNT_RECOMMLIST');
        return $page;
    }

    /**
     * Opens my-password page
     *
     * @return UserChangePassword
     */
    public function openChangePasswordPageInAccountMenu()
    {
        $this->clickLinkOnAccountMenu('CHANGE_PASSWORD');
        $page = new UserChangePassword($this->user);
        $this->seePageTitle($page, 'CHANGE_PASSWORD');
        return $page;
    }

    /** @return MyReviews */
    /* TODO: does not exist OXDEV
     * public function openMyReviewsPage(): MyReviews
    {
        $this->clickLinkOnAccountMenu('MY_REVIEWS');
        $page = new MyReviews($this->user);
        $this->seePageTitle($page, 'MY_REVIEWS');
        return $page;
    }*/

    public function dontSeeMyReviewsLink(): void
    {
        $this->dontSeeLinkInAccountMenu('MY_REVIEWS');
    }

    public function dontSeeMyReviewsPageTitle(): void
    {
        $page = new MyReviews($this->user);
        $this->dontSeePageTitle($page, 'MY_REVIEWS');
    }

    /** @param int $cnt */
    public function seeNumberOnMyReviewsBadge(int $cnt): void
    {
        $I = $this->user;
        $I->see(
            (string) $cnt,
            sprintf(
                '%s%s',
                Locator::contains("$this->accountMenu li a", Translator::translate('MY_REVIEWS')),
                Locator::find('span', ['class' => 'badge'])
            )
        );
    }

    public function openMyDownloadsPage(): MyDownloads
    {
        $this->clickLinkOnAccountMenu('MY_DOWNLOADS');
        $page = new MyDownloads($this->user);
        $this->seePageTitle($page, 'PAGE_TITLE_ACCOUNT_DOWNLOADS');
        return $page;
    }

    public function openMyCompareListPage(): ProductCompare
    {
        $this->clickLinkOnAccountMenu('MY_PRODUCT_COMPARISON');
        $page = new ProductCompare($this->user);
        $this->seePageTitle($page, 'COMPARE');
        return $page;
    }

    /** @param string $title */
    private function clickLinkOnAccountMenu(string $title): void
    {
        $I = $this->user;
        $I->waitForElement($this->accountMenu);
        $I->click($this->accountMenu);
        $I->waitForElement($this->accountWidget);
        $I->click(Translator::translate($title), $this->accountWidget);
        $I->waitForPageLoad();
    }

    /** @param string $title */
    private function dontSeeLinkInAccountMenu(string $title): void
    {
        $I = $this->user;
        $I->waitForElement($this->accountMenu);
        $I->dontSee(Translator::translate($title), $this->accountMenu);
    }

    /**
     * @param Page $page
     * @param string $title
     */
    private function seePageTitle(Page $page, string $title): void
    {
        $this->user->see(Translator::translate($title), $page->headerTitle);
    }

    /**
     * @param Page $page
     * @param string $title
     */
    private function dontSeePageTitle(Page $page, string $title): void
    {
        $this->user->dontSee(Translator::translate($title), $page->headerTitle);
    }
}
