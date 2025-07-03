<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page;

use Codeception\Actor;
use OxidEsales\Codeception\Page\Component\CookieNotice;
use OxidEsales\Codeception\Page\Component\MaintenancePage;

class Page
{
    use CookieNotice;
    use MaintenancePage;

    public string $URL = '';
    public string $breadCrumb = '.breadcrumb';
    protected Actor $user;

    public function __construct(Actor $I)
    {
        $this->user = $I;
    }

    public function route(mixed $params): string
    {
        return $this->URL . '/index.php?' . http_build_query($params);
    }

    public function seeOnBreadCrumb(string $breadCrumb): self
    {
        $I = $this->user;
        $I->assertStringContainsString($breadCrumb, $this->clearNewLines($I->grabTextFrom($this->breadCrumb)));
        return $this;
    }

    // Removes \n signs and leading spaces from string. Keeps only single space in the ends of each row.
    private function clearNewLines(string $line): string
    {
        return trim(preg_replace("/[\t\r\n]+/", '', $line));
    }
}
