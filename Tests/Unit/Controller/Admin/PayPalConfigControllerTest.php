<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Controller\Admin;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalConfigController;

final class PayPalConfigControllerTest extends UnitTestCase
{
    /** @var PayPalConfigController */
    private $sut;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new PayPalConfigController();
    }

    public function testGetSignUpMerchantIntegrationLink(): void
    {
        $url = $this->sut->getSandboxSignUpMerchantIntegrationLink();
        $urlInfo = parse_url($url);
        $this->assertNotEmpty($urlInfo['query']);
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
