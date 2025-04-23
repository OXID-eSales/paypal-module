<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\PrivateSales;

use OxidEsales\Codeception\Page\Component\UserForm;
use OxidEsales\Codeception\Page\Page;

/**
 * Class for open-account page
 * @package OxidEsales\Codeception\Page\Account
 */
class Registration extends Page
{
    use UserForm;

    // include url of current page
    public string $URL = '/en/open-account';

    //save form button
    public $saveFormButton = '#accUserSaveTop';

    public $confirmAGBOption = '#orderConfirmAgbBottom';

    public $confirmNewsletter = 'blnewssubscribed';

    /**
     * @return $this
     */
    public function registerUser()
    {
        $I = $this->user;
        $I->retryClick($this->saveFormButton);
        $I->waitForPageLoad();
        return $this;
    }

    /**
     * @return $this
     */
    public function confirmAGB()
    {
        $I = $this->user;
        $I->checkOption($this->confirmAGBOption);
        $I->seeCheckboxIsChecked($this->confirmAGBOption);
        return $this;
    }

    /**
     * @return $this
     */
    public function confirmNewsletterSubscription()
    {
        $I = $this->user;
        $I->checkOption($this->confirmNewsletter);
        $I->seeCheckboxIsChecked($this->confirmNewsletter);
        return $this;
    }
}
