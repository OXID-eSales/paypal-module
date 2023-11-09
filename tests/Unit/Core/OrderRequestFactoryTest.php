<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Order;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Api\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use PHPUnit\Framework\MockObject\MockBuilder;

class OrderRequestFactoryTest extends TestCase
{
    public function testGetRequest()
    {
        $this->markTestSkipped();
        $sut = new OrderRequestFactory();
        /** @var MockBuilder $orderMockBuilder */
        $orderMockBuilder = $this->getMockBuilder(Order::class);
        $orderMockBuilder->setMethods(['getId', 'getOrderCurrency', 'getTotalOrderSum']);
        $orderMock = $orderMockBuilder->getMock();

        $orderMock->method('getId')->willReturn('123');
        $currency = new stdClass();
        $currency->name = 'USD';
        $orderMock->method('getOrderCurrency')->willReturn($currency);
        $orderMock->method('getTotalOrderSum')->willReturn('123.00');

        $result = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => '123',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '123.00'
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            json_encode(
                $result
            ),
            json_encode(
                $sut->getRequest(
                    $orderMock,
                    OrderRequest::INTENT_CAPTURE
                )
            )
        );
    }
}
