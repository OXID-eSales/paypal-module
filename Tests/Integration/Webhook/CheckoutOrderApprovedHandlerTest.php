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
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;


final class CheckoutOrderApprovedHandlerTest extends UnitTestCase
{
    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], 'CHECKOUT.ORDER.APPROVED');

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = [
            'resource' => [
                'id' => 'PAYPALID123456789'
            ]
        ];
        $event = new WebhookEvent($data, 'CHECKOUT.ORDER.APPROVED');

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::byPayPalOrderId('PAYPALID123456789')->getMessage());

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderApproved(): void
    {
        $data = $this->getRequestData();
        $event = new WebhookEvent($data, 'CHECKOUT.ORDER.APPROVED');

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
            ->onlyMethods(['getServiceFromContainer'])
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
            ->method('markOrderPaid');

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

    private function getRequestData(): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/checkout_order_approved.json');

        return json_decode($json, true);
    }
}