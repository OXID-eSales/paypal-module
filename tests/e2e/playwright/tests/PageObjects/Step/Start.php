<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Page\Info\NewsletterSubscription;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * Class Start
 * @package OxidEsales\Codeception\Step
 */
class Start extends Step
{
    /**
     * @param string $userEmail
     * @param string $userName
     * @param string $userLastName
     *
     * @return NewsletterSubscription
     */
    public function registerUserForNewsletter(string $userEmail, string $userName, string $userLastName)
    {
        $I = $this->user;
        $homePage = new Home($I);
        $newsletterPage = $homePage->subscribeForNewsletter($userEmail);
        $newsletterPage->enterUserData($userEmail, $userName, $userLastName)->subscribe();
        $I->see(Translator::translate('MESSAGE_THANKYOU_FOR_SUBSCRIBING_NEWSLETTERS'));
        return $newsletterPage;
    }

    /**
     * @param string $userName
     * @param string $userPassword
     *
     * @return Home
     */
    public function loginOnStartPage(string $userName, string $userPassword)
    {
        $I = $this->user;
        $startPage = $I->openShop();
        // if snapshot exists - skipping login
       /* if ($I->loadSessionSnapshot('login')) {
            return $startPage;
        }*/
        $startPage = $startPage->loginUser($userName, $userPassword);
      //  $I->saveSessionSnapshot('login');
        return $startPage;
    }
}
