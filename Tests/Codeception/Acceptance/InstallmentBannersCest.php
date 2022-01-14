<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Home;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;

/**
 * @group osc_paypal
 * @group osc_paypal_banners
 */
final class InstallmentBannersCest extends BaseCest
{
    /**
     * @param AcceptanceTester $I
     */
    public function shopStartPageLoads(AcceptanceTester $I)
    {
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);

        $I->waitForText("Home");
        $I->waitForText("Week's Special");
    }
}