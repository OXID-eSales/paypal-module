<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Checkout;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Account\UserOrderHistory;
use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Page\Page;

class ThankYou extends Page
{
    public string $URL = '/index.php?cl=thankyou&lang=1';
    public string $breadCrumb = '.breadcrumb';
    public string $thankYouPage = '#thankyouPage';
    private string $backToShop = '#backToShop';
    private string $orderHistory = '#orderHistory';
    private string $alsoBought = '(//div[@id="alsoBoughtThankyou"]//div[@class="card product-card"])[%s]';

    public function grabOrderNumber(): string
    {
        $I = $this->user;
        $I->waitForElementVisible($this->thankYouPage, 10);
        $thankYouText = $I->grabTextFrom($this->thankYouPage);
        $thankMessage = trim(sprintf(Translator::translate('REGISTERED_YOUR_ORDER'), ''));
        $result = preg_match_all("/$thankMessage\s*(?P<orderNumber>\d+)/", $thankYouText, $matches);
        $I->assertFalse(empty($result), "Order number is not empty");

        return $matches['orderNumber'][0];
    }

    public function backToShop(): Home
    {
        $I = $this->user;
        $I->click($this->backToShop);
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);
        $I->waitForPageLoad();
        return $homePage;
    }

    public function goToOrderHistory(): UserOrderHistory
    {
        $I = $this->user;
        $I->click($this->orderHistory);
        $orderHistory = new UserOrderHistory($I);
        $I->amOnPage($orderHistory->URL);
        $I->waitForPageLoad();
        return $orderHistory;
    }

    public function openAlsoBoughtProduct(int $position = 1): self
    {
        $I = $this->user;
        $I->see(Translator::translate('WHO_BOUGHT_ALSO_BOUGHT'));
        $I->retryClick(sprintf($this->alsoBought, $position));
        return $this;
    }

    public function dontSeeAlsoBought(): self
    {
        $I = $this->user;
        $I->dontSee(Translator::translate('CUSTOMERS_ALSO_BOUGHT'));
        return $this;
    }
}
