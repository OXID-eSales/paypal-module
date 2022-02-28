<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;


final class PaymentCaptureDeniedHandlerTest extends UnitTestCase
{
    public function testPaymentCaptureDenied(): void
    {
        $data = $this->getRequestData();
        $event = new WebhookEvent($data, 'PAYMENT.CAPTURE.DENIED');

        $orderMock = $this->prepareOrderMock('order_oxid');
        $paypalOrderMock = $this->preparePayPalOrderMock($data['resource']['id']);

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $handler = $this->getMockBuilder(PaymentCaptureDeniedHandler::class)
            ->setMethods(['getServiceFromContainer'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($orderRepositoryMock);
        $handler->handle($event);
    }

    private function prepareOrderMock(string $orderId): EshopModelOrder
    {
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
            ->method('markOrderPaymentFailed');

        return $mock;
    }

    private function preparePaypalOrderMock(string $orderId): PayPalOrderModel
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

    private function getRequestData(): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/payment_capture_denied.json');

        return json_decode($json, true);
    }
}