<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;

final class CheckoutOrderApprovedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'CHECKOUT.ORDER.APPROVED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('checkout_order_approved.json');
        $payPalOrderId = $data['resource']['id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage());

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderApprovedIsAlreadyCompleted(): void
    {
        $data = $this->getRequestData('checkout_order_approved_pui_v2.json');
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock('oxid', 'markOrderPaid', 'never');
        $paypalOrderMock = $this->getMockBuilder(PayPalOrderModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->expects($this->never())
            ->method('getOrderService');

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->onlyMethods(['getOrderRepository', 'getPaymentService'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);
        $handler->expects($this->never())
            ->method('getPaymentService');

        $handler->handle($event);
    }

    public function testCheckoutOrderApprovedNeedsCapture(): void
    {
        $data = $this->getRequestData('checkout_order_approved.json');
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);
        $payPalOrderId = $data['resource']['id'];

        //NOTE: payment service which would trigger markOrderPaid is mocked in this test
        $orderMock = $this->prepareOrderMock('oxid', 'markOrderPaid', 'never');
        $paypalOrderMock = $this->getMockBuilder(PayPalOrderModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paypalOrderMock->expects($this->any())
        ->method('getPaymentMethodId')
        ->willReturn('test_payment');

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->once())
            ->method('doCapturePayPalOrder')
            ->with($orderMock, $payPalOrderId, 'test_payment');

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->onlyMethods(['getOrderRepository', 'getPaymentService'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);
        $handler->expects($this->any())
            ->method('getPaymentService')
            ->willReturn($paymentServiceMock);

        $handler->handle($event);
    }

    public function testCheckoutOrderApprovedNeedsCaptureException(): void
    {
        $data = $this->getRequestData('checkout_order_approved.json');
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);
        $payPalOrderId = $data['resource']['id'];

        //NOTE: payment service which would trigger markOrderPaid is mocked in this test
        $orderMock = $this->prepareOrderMock('oxid', 'markOrderPaid', 'never');
        $paypalOrderMock = $this->getMockBuilder(PayPalOrderModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paypalOrderMock->expects($this->any())
            ->method('getPaymentMethodId')
            ->willReturn('test_payment');

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->once())
            ->method('doCapturePayPalOrder')
            ->willThrowException(new \Exception('hit a capture api errorr'));

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with("Error during CHECKOUT.ORDER.APPROVED for PayPal order_id '" . $payPalOrderId . "'");

        EshopRegistry::set('logger', $loggerMock);

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->onlyMethods(['getOrderRepository', 'getPaymentService'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);
        $handler->expects($this->any())
            ->method('getPaymentService')
            ->willReturn($paymentServiceMock);

        $handler->handle($event);
    }
}
