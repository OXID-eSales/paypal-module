<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidEsales\Facts\Facts;

abstract class BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
    }

    public function _after(AcceptanceTester $I): void
    {
    }

    protected function getShopUrl(): string
    {
        $facts = new Facts();

        return $facts->getShopUrl();
    }
}
