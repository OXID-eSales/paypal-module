<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;
use OxidEsales\Eshop\Application\Model\Content as EshopModelContent;

final class StaticContentTest extends BaseTestCase
{
    public function testCreateStaticContent()
    {
        $before = oxNew(EshopModelContent::class);
        $before->loadByIdent('oscpaypalpuiconfirmation');
        $before->delete();

        $deleted = oxNew(EshopModelContent::class);
        $this->assertFalse($deleted->loadByIdent('oscpaypalpuiconfirmation'));

        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->addStaticContents();

        $after = oxNew(EshopModelContent::class);
        $after->loadByIdent('oscpaypalpuiconfirmation');
        $after->loadInLang(0, $after->getId());
        $this->assertNotEmpty($after->getTitle());
        $after->loadInLang(1, $after->getId());
        $this->assertNotEmpty($after->getTitle());
    }
}