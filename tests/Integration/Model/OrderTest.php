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
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalExtendModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Constants as PayPalConstants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture as PayPalApiCapture;

final class OrderTest extends BaseTestCase
{
    private const TEST_ORDER_ID = '_testorder';
    private const TEST_PAYPAL_ORDER_ID = '1UH87839KR156544P';
    private const TEST_PAYPAL_TRANS_ID = '42311647XV020574X';

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

    public function dataProviderFinalizeOrder(): array
    {
        return [
            'wait_for_webhook' => [
                'payment' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'isOrderFinished' => false,
                'isOrderPaid' => false,
                'isWaitForWebhookTimeoutReached' => false,
                'hasOrderNumber' => false,
                'orderInProgress' => true,
                'expected' => 600 // PayPalExtendModelOrder::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS
            ],
            'wait_timeout' => [
                'payment' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'isOrderFinished' => false,
                'isOrderPaid' => false,
                'isWaitForWebhookTimeoutReached' => true,
                'hasOrderNumber' => false,
                'orderInProgress' => true,
                'expected' => 900 //PayPalExtendModelOrder::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS
            ],
            'need_call_finalize' => [
                'payment' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'isOrderFinished' => true,
                'isOrderPaid' => true,
                'isWaitForWebhookTimeoutReached' => true,  //does not matter in this case
                'hasOrderNumber' => false,
                'orderInProgress' => true,
                'expected' => 800 //PayPalExtendModelOrder::ORDER_STATE_NEED_CALL_ACDC_FINALIZE
            ],
            'wait_for_webhook_uapm_payment' => [
                'payment' => PayPalDefinitions::GIROPAY_PAYPAL_PAYMENT_ID,
                'isOrderFinished' => false,
                'isOrderPaid' => false,
                'isWaitForWebhookTimeoutReached' => false,
                'hasOrderNumber' => false,
                'orderInProgress' => true,
                'expected' => 600 //PayPalExtendModelOrder::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS
            ],
            'wait_for_webhook_standard_payment' => [
                'payment' => 'oxidcashondel',
                'isOrderFinished' => false,
                'isOrderPaid' => false,
                'isWaitForWebhookTimeoutReached' => false,
                'hasOrderNumber' => false,
                'orderInProgress' => true,
                'expected' => 5 //EshopModelOrder::ORDER_STATE_INVALIDPAYMENT  //sure, we use empty basket
            ],
            'non_dropoff_acdc' => [
                'payment' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'isOrderFinished' => false,
                'isOrderPaid' => false,
                'isWaitForWebhookTimeoutReached' => false,
                'hasOrderNumber' => false,
                'orderInProgress' => false,
                'expected' => 5 //EshopModelOrder::ORDER_STATE_INVALIDPAYMENT  //sure, we use empty basket
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFinalizeOrder
     */
    public function testFinalizeOrder(
        string $paymentId,
        bool $isOrderFinished,
        bool $isOrderPaid,
        bool $isWaitForWebhookTimeoutReached,
        bool $hasOrderNumber,
        bool $orderInProgress,
        int $expected
    ): void {

        EshopRegistry::getSession()->setVariable('sess_challenge', self::TEST_ORDER_ID);

        EshopRegistry::getSession()->setVariable(
            PayPalConstants::SESSION_CHECKOUT_ORDER_ID,
            $orderInProgress ? self::TEST_PAYPAL_ORDER_ID : null
        );

        $basket = oxNew(EshopModelBasket::class);
        $user = oxNew(EshopModelUser::class);

        $orderMock = $this->getOrderMock(
            $isOrderFinished,
            $isOrderPaid,
            $isWaitForWebhookTimeoutReached,
            $hasOrderNumber
        );

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['getSessionPaymentId', 'isOrderExecutionInProgress'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->any())
            ->method('getSessionPaymentId')
            ->willReturn($paymentId);
        $paymentServiceMock->expects($this->any())
            ->method('isOrderExecutionInProgress')
            ->willReturn($orderInProgress);

        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);

        $result = $orderMock->finalizeOrder($basket, $user);

        $this->assertSame($expected, $result);
    }

    public function testFinalizeOrderAfterExternalPaymentOrderLoadError(): void
    {
        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(['isLoaded'])
            ->getMock();

        $orderMock->expects($this->once())
            ->method('isLoaded')
            ->willReturn(false);

        $this->expectException(\OxidSolutionCatalysts\PayPal\Exception\PayPalException::class);
        $this->expectExceptionMessage(
            'uAPM-Payment error. We might have PayPal order _testorder with incomplete shop order'
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_ORDER_ID);
    }

    public function testFinalizeOrderAfterExternalPaymentACDCForceFetchCompleted(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest();

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment'
                ]
            )
            ->getMock();

        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);
        $orderMock->expects($this->once())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('getOrderPaymentCapture');
        $orderMock->expects($this->never())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->once())
            ->method('extractTransactionId')
            ->willReturn(self::TEST_PAYPAL_TRANS_ID);
        $orderMock->expects($this->once())
            ->method('setTransId')
            ->with($this->equalTo(self::TEST_PAYPAL_TRANS_ID));
        $orderMock->expects($this->once())
            ->method('isPayPalOrderCompleted')
            ->willReturn(true);
        $orderMock->expects($this->once())
            ->method('_sendOrderByEmail');

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
            ]
        );
        $this->assertFalse($orderMock->hasOrderNumber());

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID, true);

        $this->assertTrue($orderMock->hasOrderNumber());
    }

    public function testFinalizeOrderAfterExternalPaymentACDCForceFetchNotCompleted(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest('once', 'never');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment'
                ]
            )
            ->getMock();

        $orderMock->expects($this->never())
            ->method('getOrderPaymentCapture');
        $orderMock->expects($this->never())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);
        $orderMock->expects($this->once())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('extractTransactionId');
        $orderMock->expects($this->never())
            ->method('setTransId');
        $orderMock->expects($this->once())
            ->method('isPayPalOrderCompleted')
            ->willReturn(false);
        $orderMock->expects($this->never())
            ->method('_sendOrderByEmail');

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
            ]
        );

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage(
            (PayPalException::cannotFinalizeOrderAfterExternalPayment(
                self::TEST_PAYPAL_ORDER_ID,
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
            )
            )->getMessage()
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID, true);
    }

    public function testFinalizeOrderAfterExternalPaymentACDCNoForceFetch(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest('never', 'never');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment'
                ]
            )
            ->getMock();

        $captureMock = $this->getMockBuilder(PayPalApiCapture::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureMock->id = self::TEST_PAYPAL_TRANS_ID;

        $orderMock->expects($this->once())
            ->method('getOrderPaymentCapture')
            ->willReturn($captureMock);
        $orderMock->expects($this->never())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);
        $orderMock->expects($this->once())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('extractTransactionId');
        $orderMock->expects($this->once())
            ->method('setTransId')
            ->with($this->equalTo(self::TEST_PAYPAL_TRANS_ID));
        $orderMock->expects($this->never())
            ->method('isPayPalOrderCompleted');
        $orderMock->expects($this->once())
            ->method('_sendOrderByEmail');

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
            ]
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID);
    }

    public function testFinalizeOrderAfterExternalPaymentBailOutBecauseNonPayPalPayment(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest('never', 'never');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment'
                ]
            )
            ->getMock();

        $orderMock->expects($this->never())
            ->method('getOrderPaymentCapture');
        $orderMock->expects($this->never())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);
        $orderMock->expects($this->never())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('extractTransactionId');
        $orderMock->expects($this->never())
            ->method('setTransId');
        $orderMock->expects($this->never())
            ->method('isPayPalOrderCompleted');
        $orderMock->expects($this->never())
            ->method('_sendOrderByEmail');

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => 'oxidcashondel'
            ]
        );

        $this->expectException(PayPalException::class);
        $this->expectExceptionMessage(
            (PayPalException::cannotFinalizeOrderAfterExternalPayment(
                self::TEST_PAYPAL_ORDER_ID,
                'oxidcashondel'
            )
            )->getMessage()
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID, true);
    }

    public function testFinalizeOrderAfterExternalUapmPayment(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest('never', 'never');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment',
                    'doCapturePayPalOrder'
                ]
            )
            ->getMock();

        $captureMock = $this->getMockBuilder(PayPalApiCapture::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureMock->id = self::TEST_PAYPAL_TRANS_ID;

        //@TODO this is solution for error, but it breaks the test, reactor needed
        $orderMock->expects($this->once())
            ->method('doCapturePayPalOrder')
            ->willReturn(true);
        $orderMock->expects($this->once())
            ->method('getOrderPaymentCapture')
            ->willReturn($captureMock);
        $orderMock->expects($this->once())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->willReturn($paymentServiceMock);
        $orderMock->expects($this->once())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('extractTransactionId')
            ->willReturn(self::TEST_PAYPAL_TRANS_ID);
        $orderMock->expects($this->once())
            ->method('setTransId')
            ->with($this->equalTo(self::TEST_PAYPAL_TRANS_ID));
        $orderMock->expects($this->never())
            ->method('isPayPalOrderCompleted')
            ->willReturn(false);
        $orderMock->expects($this->once())
            ->method('_sendOrderByEmail');

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => PayPalDefinitions::GIROPAY_PAYPAL_PAYMENT_ID
            ]
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID, true);
    }

    public function testFinalizeOrderAfterExternalStandardPaymentManually(): void
    {
        $paymentServiceMock = $this->prepareFinalizeTest('never', 'once');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'afterOrderCleanUp',
                    'isPayPalOrderCompleted',
                    'markOrderPaid',
                    'setTransId',
                    'extractTransactionId',
                    '_sendOrderByEmail',
                    'getOrderPaymentCapture',
                    'doExecutePayPalPayment',
                    '_setOrderStatus'
                ]
            )
            ->getMock();

        $moduleSettingsMock = $this->getMockBuilder(ModuleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayPalStandardCaptureStrategy'])
            ->getMock();
        $moduleSettingsMock->expects($this->once())
            ->method('getPayPalStandardCaptureStrategy')
            ->willReturn('manually');

        $captureMock = $this->getMockBuilder(PayPalApiCapture::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captureMock->id = self::TEST_PAYPAL_TRANS_ID;

        $orderMock->expects($this->never())
            ->method('getOrderPaymentCapture')
            ->willReturn($captureMock);
        $orderMock->expects($this->never())
            ->method('doExecutePayPalPayment');
        $orderMock->expects($this->any())
            ->method('getServiceFromContainer')
            ->withConsecutive(
                [$this->equalTo(PaymentService::class)],
                [$this->equalTo(ModuleSettings::class)]
            )
            ->willReturnOnConsecutiveCalls($paymentServiceMock, $moduleSettingsMock);
        $orderMock->expects($this->once())
            ->method('afterOrderCleanUp');
        $orderMock->expects($this->never())
            ->method('extractTransactionId')
            ->willReturn(self::TEST_PAYPAL_TRANS_ID);
        $orderMock->expects($this->never())
            ->method('setTransId');
        $orderMock->expects($this->never())
            ->method('isPayPalOrderCompleted');
        $orderMock->expects($this->once())
            ->method('_sendOrderByEmail');
        $orderMock->expects($this->once())
            ->method('_setOrderStatus')
            ->with($this->equalTo('NOT_FINISHED'));

        $orderMock->load(self::TEST_ORDER_ID);
        $orderMock->assign(
            [
                'oxpaymenttype' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID
            ]
        );

        $orderMock->finalizeOrderAfterExternalPayment(self::TEST_PAYPAL_ORDER_ID, true);
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

        Registry::getSession()->setUser(oxNew(EshopModelUser::class));
        Registry::getSession()->setBasket(oxNew(EshopModelBasket::class));

        return $paymentServiceMock;
    }
}
