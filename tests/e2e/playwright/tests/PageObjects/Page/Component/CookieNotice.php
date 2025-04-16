<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component;

use OxidEsales\Codeception\Module\Translation\Translator;

trait CookieNotice
{
    private string $cookieNoteId = '#cookieNote';
    private string $closeButton = '#cookieNote button.btn-close';
    private string $reject = '#cookieNote .cancelCookie a';

    public function closeCookieNotice(): self
    {
        $I = $this->user;
        $I->click($this->closeButton);
        return $this;
    }

    public function rejectCookies(): self
    {
        $I = $this->user;
        $I->click($this->reject);
        return $this;
    }

    public function seeRejectInfo(): self
    {
        $I = $this->user;
        $I->see(Translator::translate('INFO_ABOUT_COOKIES'));
        return $this;
    }

    public function seeCookieNotice(): self
    {
        $I = $this->user;
        $I->seeElement($this->cookieNoteId);
        return $this;
    }

    public function dontSeeCookieNotice(): self
    {
        $I = $this->user;
        $I->dontSeeElement($this->cookieNoteId);
        return $this;
    }
}
