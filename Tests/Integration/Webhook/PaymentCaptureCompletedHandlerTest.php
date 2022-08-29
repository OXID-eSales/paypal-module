<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */


declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;


final class PaymentCaptureCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.COMPLETED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], self::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = [
            'resource' => [
                'id' => self::TEST_RESOURCE_ID
            ]
        ];
        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalTransactionId(self::TEST_RESOURCE_ID)->getMessage()
        );

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'api_v1' => [
                'payment_capture_completed_v1.json'
            ],
            'api_v2' => [
                'payment_capture_completed_v2.json'
            ],
        ];
    }

    /**
     * @dataProvider dataProviderWebhookEvent
     */
    public function testPaymentCaptureCompleted(string $fixture): void
    {
        $data = $this->getRequestData($fixture);
        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock();
        $paypalOrderMock = $this->preparePayPalOrderMock($data['resource']['id']);

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalTransactionId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $orderServiceMock = $this->getMockBuilder(PayPalApiOrders::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('showOrderDetails')
            ->willReturn($this->getOrderDetails());

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->expects($this->once())
            ->method('getOrderService')
            ->willReturn($orderServiceMock);

        EshopRegistry::set(ServiceFactory::class, $serviceFactoryMock);

        $handler = $this->getMockBuilder(PaymentCaptureCompletedHandler::class)
            ->setMethods(['getServiceFromContainer'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($orderRepositoryMock);
        $handler->handle($event);
    }

    private function getOrderDetails(): ApiOrderResponse
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/checkout_order_completed_with_pui.json');

        return new ApiOrderResponse(json_decode($json, true));
    }
}