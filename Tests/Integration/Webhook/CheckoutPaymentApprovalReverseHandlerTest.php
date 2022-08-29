<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutPaymentApprovalReverseHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;

final class CheckoutPaymentApprovalReverseHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';

    public const HANDLER_CLASS = CheckoutPaymentApprovalReverseHandler::class;

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_approval_reversed_pui_v1.json');
        $payPalOrderId = $data['resource']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage()
        );

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testPuiPaymentCaptureDenied(): void
    {
        $data = $this->getRequestData('payment_approval_reversed_pui_v1.json');
        $payPalOrderId = $data['resource']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock('order_oxid', 'markOrderPaymentFailed');
        $paypalOrderMock = $this->preparePayPalOrderMock($orderMock->getId(), $payPalOrderId);

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->expects($this->never())
            ->method('getOrderService');

        EshopRegistry::set(ServiceFactory::class, $serviceFactoryMock);

        $handler = $this->getMockBuilder(static::HANDLER_CLASS)
            ->setMethods(['getOrderRepository'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);
        $handler->handle($event);
    }
}
