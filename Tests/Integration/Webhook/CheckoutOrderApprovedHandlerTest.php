<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;

final class CheckoutOrderApprovedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const FIXTURE_NAME = 'checkout_order_approved.json';

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
        $data = [
            'resource' => [
                'id' => self::TEST_RESOURCE_ID
            ]
        ];
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::byPayPalOrderId(self::TEST_RESOURCE_ID)->getMessage());

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderApproved(): void
    {
        $data = $this->getRequestData(self::FIXTURE_NAME);
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock();
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

        $data = [
            'status' => 'COMPLETED',
            'purchase_units' => [
                0 => [
                    'payments' => [
                        'captures' => [
                            0 => [
                                'status' => 'COMPLETED'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->setServiceFactoryMock($data);

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->setMethods(['getOrderRepository'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);
        $handler->handle($event);
    }

    private function setServiceFactoryMock(array $data): void
    {
        $response = new ApiOrderResponse($data);

        $orderServiceMock = $this->getMockBuilder(PayPalApiOrders::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('capturePaymentForOrder')
            ->willReturn($response);

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->getMock();
        $serviceFactoryMock->expects($this->any())
            ->method('getOrderService')
            ->willReturn($orderServiceMock);

        EshopRegistry::set(ServiceFactory::class, $serviceFactoryMock);
    }
}
