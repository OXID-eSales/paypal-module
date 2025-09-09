<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Model;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\RegisterTest;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Core\Constants as PayPalConstants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Model\Order;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture as PayPalApiCapture;

final class OrderTest extends BaseTestCase
{
    use ServiceContainer;

    private const TEST_ORDER_ID = '_testorder';
    private const TEST_PAYPAL_ORDER_ID = '1UH87839KR156544P';
    private const TEST_PAYPAL_TRANS_ID = '42311647XV020574X';
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = $this->getServiceFromContainer(PaymentService::class);
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
        $this->assertFalse($order->hasOrderNumber());

        $order->setOrderNumber();

        $order->load(self::TEST_ORDER_ID);
        $orderNumber = $order->getFieldData('oxordernr');
        $this->assertGreaterThan(0, (int) $order->getFieldData('oxordernr'));
        $this->assertTrue($order->hasOrderNumber());

        //calling Order::setOrderNumber() once more must not change the number
        $order->setOrderNumber();

        $order->load(self::TEST_ORDER_ID);
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
            ->onlyMethods(['isPayPalOrderCompleted', 'isOrderFinished', 'isOrderPaid', 'isWaitForWebhookTimeoutReached', 'load']) // Exclude finalizeOrder from mocking
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
        $orderMock->setPaymentService($paymentServiceMock);

        $session = EshopRegistry::getSession();
        $session->setVariable('sess_challenge', 'test_challenge');
        EshopRegistry::set(Session::class, $session);

        $result = $orderMock->finalizeOrder($oBasket, $oUser);

        $this->assertEquals(
            \OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS,
            $result,
            'Expected ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS (600), got: ' . $result
        );
    }


    public function testFinalizeOrderAfterExternalPaymentOrderLoadError(): void
    {
        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
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
            'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'oxtransstatus' => 'ACDC_PENDING',
        ]);
        $order->save();

        $mockOrder = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(['_sendOrderByEmail'])
            ->getMock();
        $mockOrder->expects($this->once())
        ->method('_sendOrderByEmail')
            ->willReturn(true);

        $mockOrder->setId($order->getId());
        $mockOrder->load($order->getId());

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_COMPLETED;
        $paypalApiOrder->purchase_units = [
            (object)[
                'payments' => (object)[
                    'captures' => [
                        (object)[
                            'id' => $captureId,
                            'status' => \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_COMPLETED,
                        ],
                    ],
                ],
            ],
        ];

        $orderServiceMock = $this->createMock(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class);
        $orderServiceMock->expects($this->exactly(2))
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
            $this->createMock(Logger::class),
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
            'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'oxtransstatus' => \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_PAYER_ACTION_REQUIRED
        ]);
        $order->save();

        $mockOrder = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(['_sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('_sendOrderByEmail')
            ->willReturn(true);

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_PAYER_ACTION_REQUIRED;

        $orderServiceMock = $this->createMock(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class);
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
            $this->createMock(Logger::class),
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

        $order = oxNew(\OxidSolutionCatalysts\PayPal\Model\Order::class);
        $order->setId(self::TEST_ORDER_ID);
        $order->assign([
            'oxuserid' => '_testuser',
            'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            'oxtransstatus' => \OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_ACDCINPROGRESS,
        ]);
        $order->save();
        $order->load(self::TEST_ORDER_ID);

        $this->assertTrue($order->isLoaded(), 'Order was not properly loaded.');

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_COMPLETED;
        $paypalApiOrder->purchase_units = [
            (object)[
                'payments' => (object)[
                    'captures' => [
                        (object)[
                            'id' => $captureId,
                            'status' => \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_COMPLETED,
                        ],
                    ],
                ],
            ],
        ];

        $mockOrder = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(['_sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('_sendOrderByEmail')
            ->willReturn(true);

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_PAYER_ACTION_REQUIRED;

        $orderServiceMock = $this->createMock(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class);
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
            $this->createMock(Logger::class),
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

        $paypalApiOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order();
        $paypalApiOrder->id = $payPalOrderId;
        $paypalApiOrder->status = \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order::STATUS_PAYER_ACTION_REQUIRED;


        $orderServiceMock = $this->createMock(\OxidSolutionCatalysts\PayPalApi\Service\Orders::class);
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
            $this->createMock(Logger::class),
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactoryMock,
            EshopRegistry::get(PatchRequestFactory::class),
            $this->getServiceFromContainer(OrderRequestFactory::class)
        );

        $mockOrder = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(['_sendOrderByEmail'])
            ->getMock();

        $mockOrder->expects($this->never())
            ->method('_sendOrderByEmail')
            ->willReturn(true);

        $mockOrder->setPaymentService($paymentService);
        $mockOrder->load(self::TEST_ORDER_ID);
        $mockOrder->assign(
            [
                'oxpaymenttype' => 'oxidcashondel'
            ]
        );

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage("uAPM-Payment error.");
        $mockOrder->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
    }

    public function testFinalizeOrderAfterExternalStandardPaymentManually(): void
    {
        $payPalOrderId = self::TEST_PAYPAL_ORDER_ID;
        $forceFetchDetails = false;

        $order = $this->prepareEmptyOrder();
        $order->assign([
            'oxtransstatus' => \OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_SESSIONPAYMENT_INPROGRESS,
            'oxuserid' => '_testuser',
            'oxbillcountryid' => 'a7c40f631fc920687.20179984',
            'oxdelcountryid' => 'a7c40f631fc920687.20179984',
            'oxpaymentid' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
        ]);
        $order->save();
        $this->assertTrue($order->isLoaded(), 'Order was not loaded properly.');

        $mockOrder = $this->getMockBuilder(\OxidSolutionCatalysts\PayPal\Model\Order::class)
            ->onlyMethods(['_sendOrderByEmail', 'getFieldData'])
            ->getMock();
        $mockOrder->expects($this->once())
        ->method('_sendOrderByEmail')
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

    private function prepareFinalizeTest(
        string $fetchOrderFields = 'once',
        string $trackPayPalOrder = 'once'
    ): PaymentService {
        $this->prepareEmptyOrder();

        $apiOrderMock = $this->getMockBuilder(PayPalApiOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchOrderFields', 'trackPayPalOrder'])
            ->getMock();
        $paymentServiceMock->expects($this->$fetchOrderFields())
            ->method('fetchOrderFields')
            ->willReturn($apiOrderMock);
        $paymentServiceMock->expects($this->$trackPayPalOrder())
            ->method('trackPayPalOrder');

        EshopRegistry::getSession()->setUser(oxNew(EshopModelUser::class));
        EshopRegistry::getSession()->setBasket(oxNew(EshopModelBasket::class));

        return $paymentServiceMock;
    }

    private function patchMock($orderMock)
    {
        $orderMock->setModuleSettings($this->getServiceFromContainer(ModuleSettings::class));
        $orderMock->setOrderProcessTrackingService(new OrderProcessTrackingService);
        $orderMock->setPaymentService($this->getServiceFromContainer(PaymentService::class));
        return $orderMock;
    }
}
