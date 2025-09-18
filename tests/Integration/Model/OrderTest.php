<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Model;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Config as EshopConfig;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\RegisterTest;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Model\Order as PaypalOrder;
use Psr\Log\LoggerInterface;

final class OrderTest extends BaseTestCase
{
    use ServiceContainer;

    private const TEST_ORDER_ID = 'testorder_id';
    private const TEST_PAYPAL_ORDER_ID = '1UH87839KR156544P';
    private const TEST_PAYPAL_TRANS_ID = '42311647XV020574X';

    protected function setUp(): void
    {
        parent::setUp();
    }


    protected function tearDown(): void
    {
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

    public function testHasNumberSetNumber(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertEquals(0, $order->getFieldData('oxordernr'));

        $order->setOrderNumber();

        $orderNumber = $order->getFieldData('oxordernr');
        $this->assertGreaterThan(0, (int) $order->getFieldData('oxordernr'));
        $this->assertTrue($order->hasOrderNumber());

        $this->assertEquals($orderNumber, $order->getFieldData('oxordernr'));
    }

    public function testIsWaitForWebhookTimeoutReached(): void
    {
        $order = $this->prepareEmptyOrder();
        $order->assign(
            [
                'oxorderdate' => date('Y-m-d H:i:s')
            ]
        );

        $this->assertFalse($order->isWaitForWebhookTimeoutReached());

        $order->assign(
            [
                'oxorderdate' => '2022-04-01 11:11:11'
            ]
        );

        $this->assertTrue($order->isWaitForWebhookTimeoutReached());
    }

    public function testIsOrderFinished(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertFalse($order->isOrderFinished());

        $order->assign(
            [
                'oxtransstatus' => 'OK'
            ]
        );

        $this->assertTrue($order->isOrderFinished());
    }

    public function testIsOrderPaid(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertFalse($order->isOrderPaid());

        $order->assign(
            [
                'oxpaid' => date('Y-m-d h:i:s')
            ]
        );

        $this->assertTrue($order->isOrderPaid());
    }

    public function testMarkOrderPaid(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertFalse($order->isOrderPaid());

        $order->markOrderPaid();

        $this->assertTrue($order->isOrderPaid());
        $this->assertTrue($order->isOrderFinished());
    }

    public function testSetTransId(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertSame('', $order->getFieldData('oxtransid'));

        $order->setTransId('test_trans_id');

        $this->assertSame('test_trans_id', $order->getFieldData('oxtransid'));
    }

    public function testSavePuiInvoiceNr(): void
    {
        $order = $this->prepareEmptyOrder();

        $this->assertEmpty($order->getFieldData('oxinvoicenr'));

        $order->savePuiInvoiceNr('test-pui-1234');

        $this->assertSame('test-pui-1234', $order->getFieldData('oxinvoicenr'));
    }

    public function testFinalizeOrder(): void
    {
        $oBasket = oxNew(EshopModelBasket::class);
        $oUser = oxNew(EshopModelUser::class);

        $orderMock = $this->getMockBuilder(\OxidSolutionCatalysts\PayPal\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isPayPalOrderCompleted',
                'isOrderFinished',
                'isOrderPaid',
                'isWaitForWebhookTimeoutReached',
                'load',
                'validateOrder'
            ])
            ->getMock();

        $orderMock->method('isPayPalOrderCompleted')
            ->willReturn(true);
        $orderMock->method('isOrderFinished')
            ->willReturn(false);
        $orderMock->method('isOrderPaid')
            ->willReturn(false);
        $orderMock->method('isWaitForWebhookTimeoutReached')
            ->willReturn(false);

        $orderMock->method('load')
            ->willReturn(true);

        $orderMock->assign([
            'oxtransstatus' => 'OK',
            'oxshopid' => 1,
        ]);

        $paymentServiceMock = $this->createMock(PaymentService::class);
        $paymentServiceMock->method('isPayPalPayment')
            ->willReturn(true);
        $paymentServiceMock->method('isOrderExecutionInProgress')
            ->willReturn(true);

        $orderMock = $this->patchMock($orderMock);
        $orderMock->method('validateOrder')
            ->willReturn(1);
        $orderMock->setPaymentService($paymentServiceMock);
        $orderMock->setLogger($this->createMock(LoggerInterface::class));
        $session = EshopRegistry::getSession();
        $session->setVariable('sess_challenge', 'test_challenge');
        EshopRegistry::set(Session::class, $session);

        $result = $orderMock->finalizeOrder($oBasket, $oUser);

        $this->assertEquals(
            PaypalOrder::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS,
            $result,
            'Expected ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS (600), got: ' . $result
        );
    }


    public function testFinalizeOrderAfterExternalPaymentOrderLoadError(): void
    {
        $orderMock = $this->getMockBuilder(PaypalOrder::class)
            ->onlyMethods(['isLoaded'])
            ->getMock();

        $orderMock->expects($this->once())
            ->method('isLoaded')
            ->willReturn(false);
        $orderMock = $this->patchMock($orderMock);
        $this->expectException(\OxidSolutionCatalysts\PayPal\Exception\PayPalException::class);
        $this->expectExceptionMessage('uAPM-Payment error.');

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_ORDER_ID);
    }


    public function testFinalizeOrderAfterExternalPaymentACDCForceFetchCompleted(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $captureId = '1UH87839KR156555P';
        $forceFetchDetails = true;

        $order = $this->prepareEmptyOrder();
        $order->assign([
            'OXPAYMENTTYPE' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'OXTRANSSTATUS' => 'ACDC_PENDING',
        ]);
        $order->save();

        $mockOrder = $this->getMockBuilder(PaypalOrder::class)
            ->onlyMethods(['sendOrderByEmail'])
            ->getMock();
        $mockOrder->expects($this->once())
            ->method('sendOrderByEmail')
            ->willReturn(true);

        $mockOrder->setId($order->getId());
        $mockOrder->load($order->getId());

        $paypalApiOrder = new PayPalApiOrder();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = PayPalApiOrder::STATUS_COMPLETED;
        $paypalApiOrder->purchase_units = [
            (object)[
                'payments' => (object)[
                    'captures' => [
                        (object)[
                            'id' => $captureId,
                            'status' => PayPalApiOrder::STATUS_COMPLETED,
                        ],
                    ],
                ],
            ],
        ];

        $orderServiceMock = $this->getMockBuilder(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showOrderDetails', 'setTrackingId'])
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('setTrackingId');
        $orderServiceMock->expects($this->exactly(2))
            ->method('showOrderDetails')
            ->with($this->equalTo($payPalOrderId))
            ->willReturn($paypalApiOrder);

        $serviceFactoryMock = $this->createMock(
            \OxidSolutionCatalysts\PayPal\Core\ServiceFactory::class
        );
        $serviceFactoryMock->method('getOrderService')
            ->willReturn($orderServiceMock);

        $session = EshopRegistry::getSession();
        $session->setBasket(oxNew(EshopModelBasket::class));
        $session->setUser(oxNew(EshopModelUser::class));
        EshopRegistry::set(Session::class, $session);

        $paymentService = new \OxidSolutionCatalysts\PayPal\Service\Payment(
            $session,
            $this->createMock(OrderRepository::class),
            $this->createMock(SCAValidatorInterface::class),
            $this->createMock(ModuleSettings::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactoryMock,
            EshopRegistry::get(PatchRequestFactory::class),
            $this->getServiceFromContainer(OrderRequestFactory::class)
        );

        $mockOrder->setPaymentService($paymentService);

        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);

        $this->assertEquals($captureId, $mockOrder->getFieldData('oxtransid'));
        $this->assertTrue($mockOrder->isOrderPaid());
        $this->assertTrue($mockOrder->isOrderFinished());
        $this->assertEquals(
            'OK',
            $mockOrder->getFieldData('oxtransstatus')
        );
    }

    public function testFinalizeOrderAfterExternalPaymentACDCForceFetchNotCompleted(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $forceFetchDetails = true;

        $order = $this->prepareEmptyOrder();
        $order->assign([
            'OXPAYMENTTYPE' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'OXTRANSSTATUS' => \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_PAYER_ACTION_REQUIRED
        ]);
        $order->save();

        $mockOrder = $this->getMockBuilder(PaypalOrder::class)
            ->onlyMethods(['sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('sendOrderByEmail')
            ->willReturn(true);

        $paypalApiOrder = new PayPalApiOrder();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = PayPalApiOrder::STATUS_PAYER_ACTION_REQUIRED;

        $orderServiceMock = $this->getMockBuilder(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showOrderDetails', 'setTrackingId'])
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('setTrackingId');
        $orderServiceMock->expects($this->exactly(0))
            ->method('showOrderDetails')
            ->with($this->equalTo($payPalOrderId))
            ->willReturn($paypalApiOrder);

        $serviceFactoryMock = $this->createMock(
            \OxidSolutionCatalysts\PayPal\Core\ServiceFactory::class
        );
        $serviceFactoryMock->method('getOrderService')
            ->willReturn($orderServiceMock);

        $session = EshopRegistry::getSession();
        $session->setBasket(oxNew(EshopModelBasket::class));
        $session->setUser(oxNew(EshopModelUser::class));

        $paymentService = new \OxidSolutionCatalysts\PayPal\Service\Payment(
            $session,
            $this->createMock(OrderRepository::class),
            $this->createMock(SCAValidatorInterface::class),
            $this->createMock(ModuleSettings::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactoryMock,
            EshopRegistry::get(PatchRequestFactory::class),
            $this->getServiceFromContainer(OrderRequestFactory::class)
        );

        $mockOrder->setPaymentService($paymentService);

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage("uAPM-Payment error");

        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
    }

    public function testFinalizeOrderAfterExternalPaymentACDCNoForceFetch(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $captureId = '42311647XV020574X';
        $forceFetchDetails = false;

        $order = oxNew(PaypalOrder::class);
        $order->setId(self::TEST_ORDER_ID);
        $order->assign([
            'OXUSERID' => '_testuser',
            'OXPAYMENTTYPE' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'OXTRANSSTATUS' => PaypalOrder::ORDER_STATE_ACDCINPROGRESS,
        ]);
        $order->save();
        $order->load(self::TEST_ORDER_ID);

        $this->assertTrue($order->isLoaded(), 'Order was not properly loaded.');

        $paypalApiOrder = new PayPalApiOrder();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = PayPalApiOrder::STATUS_COMPLETED;
        $paypalApiOrder->purchase_units = [
            (object)[
                'payments' => (object)[
                    'captures' => [
                        (object)[
                            'id' => $captureId,
                            'status' => PayPalApiOrder::STATUS_COMPLETED,
                        ],
                    ],
                ],
            ],
        ];

        $mockOrder = $this->getMockBuilder(PaypalOrder::class)
            ->onlyMethods(['sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('sendOrderByEmail')
            ->willReturn(true);

        $paypalApiOrder = new PayPalApiOrder();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = PayPalApiOrder::STATUS_PAYER_ACTION_REQUIRED;

        $orderServiceMock = $this->getMockBuilder(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showOrderDetails', 'setTrackingId'])
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('setTrackingId');
        $orderServiceMock->expects($this->exactly(0))
            ->method('showOrderDetails')
            ->with($this->equalTo($payPalOrderId))
            ->willReturn($paypalApiOrder);

        $serviceFactoryMock = $this->createMock(
            \OxidSolutionCatalysts\PayPal\Core\ServiceFactory::class
        );
        $serviceFactoryMock->method('getOrderService')
            ->willReturn($orderServiceMock);

        EshopRegistry::getSession()->setUser(oxNew(EshopModelUser::class));
        EshopRegistry::getSession()->setBasket(oxNew(EshopModelBasket::class));

        $paymentService = new \OxidSolutionCatalysts\PayPal\Service\Payment(
            EshopRegistry::getSession(),
            $this->createMock(OrderRepository::class),
            $this->createMock(SCAValidatorInterface::class),
            $this->createMock(ModuleSettings::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactoryMock,
            EshopRegistry::get(PatchRequestFactory::class),
            $this->getServiceFromContainer(OrderRequestFactory::class)
        );

        $mockOrder->setPaymentService($paymentService);
        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage("uAPM-Payment error");

        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
    }


    public function testFinalizeOrderAfterExternalPaymentBailOutBecauseNonPayPalPayment(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $forceFetchDetails = true;

        $paypalApiOrder = new PayPalApiOrder();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = PayPalApiOrder::STATUS_PAYER_ACTION_REQUIRED;


        $orderServiceMock = $this->getMockBuilder(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showOrderDetails', 'setTrackingId'])
            ->getMock();
        $orderServiceMock->expects($this->any())
            ->method('setTrackingId');
        $orderServiceMock->expects($this->exactly(0))
            ->method('showOrderDetails')
            ->with($this->equalTo($payPalOrderId))
            ->willReturn($paypalApiOrder);

        $serviceFactoryMock = $this->createMock(
            \OxidSolutionCatalysts\PayPal\Core\ServiceFactory::class
        );
        $serviceFactoryMock->method('getOrderService')
            ->willReturn($orderServiceMock);

        EshopRegistry::getSession()->setUser(oxNew(EshopModelUser::class));
        EshopRegistry::getSession()->setBasket(oxNew(EshopModelBasket::class));

        $paymentService = new \OxidSolutionCatalysts\PayPal\Service\Payment(
            EshopRegistry::getSession(),
            $this->createMock(OrderRepository::class),
            $this->createMock(SCAValidatorInterface::class),
            $this->createMock(ModuleSettings::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactoryMock,
            EshopRegistry::get(PatchRequestFactory::class),
            $this->getServiceFromContainer(OrderRequestFactory::class)
        );

        $mockOrder = $this->getMockBuilder(PaypalOrder::class)
            ->onlyMethods(['sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('sendOrderByEmail')
            ->willReturn(true);

        $mockOrder->setPaymentService($paymentService);
        $mockOrder->load(self::TEST_ORDER_ID);
        $mockOrder->assign(
            [
                'oxpaymenttype' => 'oxidcashondel'
            ]
        );

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage("Error during external payment order finalization");
        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
    }

    public function testFinalizeOrderAfterExternalStandardPaymentManually(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $forceFetchDetails = false;

        $order = $this->prepareEmptyOrder();
        $order->assign([
            'OXTRANSSTATUS' => \OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_SESSIONPAYMENT_INPROGRESS,
            'OXUSERID' => 'test_user_id',
            'OXBILLCOUNTRYID' => 'a7c40f631fc920687.20179984',
            'OXDELCOUNTRYID' => 'a7c40f631fc920687.20179984',
            'OXPAYMENTID' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
        ]);
        $order->save();
        $this->assertTrue($order->isLoaded(), 'Order was not loaded properly.');

        $mockOrder = $this->getMockBuilder(\OxidSolutionCatalysts\PayPal\Model\Order::class)
            ->onlyMethods(['sendOrderByEmail', 'getFieldData'])
            ->getMock();
        $mockOrder->expects($this->once())
            ->method('sendOrderByEmail')
            ->willReturn(true);

        $mockOrder->setId($order->getId());
        $mockOrder->load($order->getId());

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_COMPLETED;

        // Mock PayPal purchase units (example data)
        $paypalApiOrder->purchase_units = [
            (object)[
                'amount' => (object)[
                    'value' => '100.00', // Example amount
                    'currency_code' => 'USD' // Example currency
                ],
            ]
        ];

        $paymentServiceMock = $this->createMock(PaymentService::class);

        $paymentServiceMock->expects($this->once())
            ->method('isPayPalPayment')
            ->willReturn(true);

        $paymentServiceMock->method('fetchOrderFields')
            ->with($this->equalTo($payPalOrderId))
            ->willReturn($paypalApiOrder);

        $mockOrder->setPaymentService($paymentServiceMock);

        $mockOrder->expects($this->exactly(2))
            ->method('getFieldData');

        EshopRegistry::getSession()->setUser(oxNew(EshopModelUser::class));
        EshopRegistry::getSession()->setBasket(oxNew(EshopModelBasket::class));

        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
    }

    private function getOrderMock(
        bool $isOrderFinished,
        bool $isOrderPaid,
        bool $isWaitForWebhookTimeoutReached,
        bool $hasOrderNumber = false
    ): EshopModelOrder {
        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'isOrderFinished',
                    'isOrderPaid',
                    'isWaitForWebhookTimeoutReached',
                    'load',
                    'hasOrderNumber',
                    'getServiceFromContainer'
                ]
            )
            ->getMock();

        $orderMock->expects($this->any())
            ->method('load')
            ->with($this->equalTo(self::TEST_ORDER_ID))
            ->willReturn(true);

        $orderMock->expects($this->any())
            ->method('isOrderFinished')
            ->willReturn($isOrderFinished);

        $orderMock->expects($this->any())
            ->method('isOrderPaid')
            ->willReturn($isOrderPaid);

        $orderMock->expects($this->any())
            ->method('hasOrderNumber')
            ->willReturn($hasOrderNumber);

        $orderMock->expects($this->any())
            ->method('isWaitForWebhookTimeoutReached')
            ->willReturn($isWaitForWebhookTimeoutReached);

        return $orderMock;
    }

    private function prepareEmptyOrder(): EshopModelOrder
    {
        $order = oxNew(EshopModelOrder::class);
        $order->setId(self::TEST_ORDER_ID);
        $order->save();
        $order->load(self::TEST_ORDER_ID);

        return $order;
    }

    private function patchMock($orderMock)
    {
        $orderMock->setModuleSettings($this->getServiceFromContainer(ModuleSettings::class));
        $orderMock->setOrderProcessTrackingService(new OrderProcessTrackingService());
        $orderMock->setPaymentService($this->getServiceFromContainer(PaymentService::class));
        return $orderMock;
    }
}
