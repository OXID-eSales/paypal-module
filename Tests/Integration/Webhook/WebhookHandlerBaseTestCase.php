<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;

class WebhookHandlerBaseTestCase extends UnitTestCase
{
    public const TEST_RESOURCE_ID = 'PAYPALID123456789';

    protected function getRequestData(string $fixtureFileName): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixtureFileName);

        return json_decode($json, true);
    }

    protected function preparePaypalOrderMock(string $orderId): PayPalOrderModel
    {
        $mock = $this->getMockBuilder(PayPalOrderModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('load')
            ->with($orderId)
            ->willReturn(true);
        $mock->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);
        $mock->expects($this->once())
            ->method('setStatus');
        $mock->expects($this->once())
            ->method('save');

        return $mock;
    }

    protected function prepareOrderMock(
        string $orderId = 'order_oxid',
        string $methodName = 'markOrderPaid'
    ): EshopModelOrder {
        $mock = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('load')
            ->with($orderId)
            ->willReturn(true);
        $mock->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);
        $mock->expects($this->once())
            ->method($methodName);

        return $mock;
    }
}
