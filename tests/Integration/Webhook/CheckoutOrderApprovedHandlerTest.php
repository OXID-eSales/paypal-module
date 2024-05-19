<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;

final class CheckoutOrderApprovedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'CHECKOUT.ORDER.APPROVED';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oscpaypal_order');
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order not found.');

        $handler = oxNew(CheckoutOrderApprovedHandler::class);
        $handler->handle($event);
    }

    public function testCheckoutOrderApprovedIsAlreadyCompleted(): void
    {
        $data = $this->getRequestData('checkout_order_approved_pui_v2.json');
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $orderMock = $this->prepareOrderMock('oxid', 'markOrderPaid', 'never');
        $orderMock->expects($this->never())
            ->method('setOrderNumber');

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

        $this->prepareTestData($payPalOrderId);
        $this->assertFalse($this->hasNonZeroOrderNumber());

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->once())
            ->method('doCapturePayPalOrder');

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->onlyMethods(['getPaymentService'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getPaymentService')
            ->willReturn($paymentServiceMock);

        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId);
        $this->assertTrue($this->hasNonZeroOrderNumber());
    }

    public function testCheckoutOrderApprovedNeedsCaptureException(): void
    {
        $data = $this->getRequestData('checkout_order_approved.json');
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);
        $payPalOrderId = $data['resource']['id'];

        $this->prepareTestData($payPalOrderId);

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->once())
            ->method('doCapturePayPalOrder')
            ->willThrowException(new \Exception('hit a capture api errorr'));

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->once())
            ->method('debug')
           ->with("Error during CHECKOUT.ORDER.APPROVED for PayPal order_id '" . $payPalOrderId . "'")
        ;
        Registry::set('logger', $loggerMock);

        $handler = $this->getMockBuilder(CheckoutOrderApprovedHandler::class)
            ->onlyMethods(['getPaymentService'])
            ->getMock();
        $handler->expects($this->any())
            ->method('getPaymentService')
            ->willReturn($paymentServiceMock);

        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId);
        $this->assertFalse($this->hasNonZeroOrderNumber());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame('0000-00-00 00:00:00', $order->getFieldData('OXPAID'));
    }

    private function hasNonZeroOrderNumber(): bool
    {
        $parameters = [
            'oxid' => self::SHOP_ORDER_ID,
        ];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->select('oxordernr')
            ->from('oxorder')
            ->where('oxid = :oxid');

        $result = $queryBuilder->setParameters($parameters)
            ->execute();

        return 0 < (int) $result->fetchOne();
    }
}
