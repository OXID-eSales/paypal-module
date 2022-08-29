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
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;

final class CheckoutOrderCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const FIXTURE_NAME = 'checkout_order_completed.json';

    public const WEBHOOK_EVENT = 'CHECKOUT.ORDER.COMPLETED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
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

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderCompleted(): void
    {
        $data = $this->getRequestData(self::FIXTURE_NAME);
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock();
        $paypalOrderMock = $this->preparePayPalOrderMock($data['resource']['id']);

        $orderServiceMock = $this->getMockBuilder(PayPalApiOrders::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('showOrderDetails')
            ->willReturn($this->getPuiOrderDetails());

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->expects($this->once())
            ->method('getOrderService')
            ->willReturn($orderServiceMock);

        EshopRegistry::set(ServiceFactory::class, $serviceFactoryMock);

        $orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())
            ->method('getShopOrderByPayPalOrderId')
            ->willReturn($orderMock);
        $orderRepositoryMock->expects($this->once())
            ->method('paypalOrderByOrderIdAndPayPalId')
            ->willReturn($paypalOrderMock);

        $handler = $this->getMockBuilder(CheckoutOrderCompletedHandler::class)
            ->onlyMethods(['getOrderRepository'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getOrderRepository')
            ->willReturn($orderRepositoryMock);

        $handler->handle($event);
    }

    private function getPuiOrderDetails(): ApiOrderResponse
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/checkout_order_completed_with_pui.json');

        return new ApiOrderResponse(json_decode($json, true));
    }
}
