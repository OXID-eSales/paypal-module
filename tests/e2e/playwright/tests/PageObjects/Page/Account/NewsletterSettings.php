<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Page\Page;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * Class for account_newsletter page
 * @package OxidEsales\Codeception\Page\Account
 */
class NewsletterSettings extends Page
{
    // include url of current page
    public string $URL = '/index.php?lang=1&cl=account_newsletter';

    // include bread crumb of current page
    public string $breadCrumb = '.breadcrumb';

    public $headerTitle = 'h1';

    public $newsletterStatusSelect = '//select[@id="status"]';

    public $newsletterSubscribeButton = '#newsletterSettingsSave';

    /**
     * Responsible for newsletter subscription
     *
     * @return $this
     */
    public function subscribeNewsletter()
    {
        $I = $this->user;
        $I->selectOption($this->newsletterStatusSelect, Translator::translate('YES'));
        $I->retryClick($this->newsletterSubscribeButton);
        $I->waitForPageLoad();
        $I->waitForText(Translator::translate('MESSAGE_NEWSLETTER_SUBSCRIPTION_SUCCESS'));
        return $this;
    }

    /**
     * Responsible for newsletter unsubscription
     *
     * @return $this
     */
    public function unSubscribeNewsletter()
    {
        $I = $this->user;
        $I->selectOption($this->newsletterStatusSelect, Translator::translate('NO'));
        $I->retryClick($this->newsletterSubscribeButton);
        $I->waitForPageLoad();
        $I->waitForText(Translator::translate('MESSAGE_NEWSLETTER_SUBSCRIPTION_CANCELED'));
        return $this;
    }

    /**
     * Check if newsletter is subscribed
     *
     * @return $this
     */
    public function seeNewsletterSubscribed()
    {
        $I = $this->user;
        $I->see(Translator::translate('YES'), $this->newsletterStatusSelect);
        return $this;
    }

    /**
     * Check if newsletter is unsubscribed
     *
     * @return $this
     */
    public function seeNewsletterUnSubscribed()
    {
        $I = $this->user;
        $I->seeOptionIsSelected($this->newsletterStatusSelect, Translator::translate('NO'));
        return $this;
    }
}
