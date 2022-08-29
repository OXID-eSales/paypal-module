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
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;

final class PaymentCaptureDeniedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.DENIED';

    public const HANDLER_CLASS = PaymentCaptureDeniedHandler::class;

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'api_v1' => [
                'payment_capture_denied_v1.json'
            ],
            'api_v2' => [
                'payment_capture_denied_v2.json'
            ],
        ];
    }

    /**
     * @dataProvider dataProviderWebhookEvent
     */
    public function testPayPalTransactionIdWithoutPayPalOrderId(string $fixture): void
    {
        $data = $this->getRequestData($fixture);
        $resourceId = $data['resource']['id'];
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                "Not enough information to handle PAYMENT.CAPTURE.DENIED with PayPal order_id '' and " .
                "PayPal transaction id '" . $resourceId . "'"
            );
        EshopRegistry::set('logger', $loggerMock);

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_capture_denied_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

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
        $data = $this->getRequestData('payment_capture_denied_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

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
