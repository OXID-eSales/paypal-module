<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Page\Page;

class MyDownloads extends Page
{
    public string $URL = '/en/my-downloads/';
    public string $breadCrumb = '.breadcrumb';
    public $headerTitle = 'h1';
}
