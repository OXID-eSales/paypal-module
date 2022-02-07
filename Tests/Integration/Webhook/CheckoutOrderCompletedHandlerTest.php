<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;


final class CheckoutOrderCompletedHandlerTest extends UnitTestCase
{
    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], 'CHECKOUT.ORDER.COMPLETED');

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundById(): void
    {
        $data = [
            'resource' => [
                'id' => 'PAYPALID123456789',
                'purchase_units' => [
                    0 => ['custom_id' => 'doesnotexist']
                ]
            ]
        ];
        $event = new WebhookEvent($data, 'CHECKOUT.ORDER.COMPLETED');

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::byOrderId('doesnotexist')->getMessage());

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderCompleted(): void
    {
        $data = [
            'resource' => [
                'id' => 'PAYPALID123456789',
                'purchase_units' => [
                    0 => ['custom_id' => 'order_oxid']
                ]
            ]
        ];
        $event = new WebhookEvent($data, 'CHECKOUT.ORDER.COMPLETED');

        $this->prepareOrderMock('order_oxid');

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
        $this->setServiceFactoryMock( $data);

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    private function prepareOrderMock(string $orderId):void
    {
        $mock = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('load')
            ->with($orderId)
            ->willReturn(true);
        $mock->expects($this->once())
            ->method('markOrderPaid');

        EshopRegistry::getUtilsObject()->setClassInstance(EshopModelOrder::class, $mock);
    }

    private function setServiceFactoryMock(array $data): void
    {
        $response = new ApiOrderResponse($data);

        $orderServiceMock =  $this->getMockBuilder(PayPalApiOrders::class)
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