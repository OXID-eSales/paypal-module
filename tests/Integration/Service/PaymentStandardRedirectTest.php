<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Payment::doExecuteStandardRedirectPayment() - creates the PayPal order of the
 * PayPal Standard redirect flow and returns the link the customer is redirected to.
 */
final class PaymentStandardRedirectTest extends BaseTestCase
{
    private const TEST_ORDER_ID = '_teststandardredirect';
    private const PAYPAL_ORDER_ID = '5O190127TN364715T';
    private const PAYER_ACTION_LINK = 'https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715T';

    /** @var array arguments of the last OrderRequestFactory::getRequest() call */
    private array $requestArguments = [];

    protected function setUp(): void
    {
        parent::setUp();

        PayPalSession::unsetPayPalOrderId();
        $this->requestArguments = [];
    }

    protected function tearDown(): void
    {
        PayPalSession::unsetPayPalOrderId();
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

    /**
     * @dataProvider captureStrategyProvider
     */
    public function testCreatesThePayPalOrderAndReturnsThePayerActionLink(
        string $captureStrategy,
        string $expectedIntent
    ): void {
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentService(
            $this->createApiOrder([
                ['href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::PAYPAL_ORDER_ID, 'rel' => 'self'],
                ['href' => self::PAYER_ACTION_LINK, 'rel' => 'payer-action'],
            ]),
            $captureStrategy
        );
        $paymentService->expects($this->once())
            ->method('trackPayPalOrder')
            ->with(
                self::TEST_ORDER_ID,
                self::PAYPAL_ORDER_ID,
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                PayPalApiOrder::STATUS_PAYER_ACTION_REQUIRED
            );

        $redirectLink = $paymentService->doExecuteStandardRedirectPayment($order, oxNew(EshopModelBasket::class));

        $this->assertSame(self::PAYER_ACTION_LINK, $redirectLink);
        $this->assertSame(self::PAYPAL_ORDER_ID, PayPalSession::getCheckoutOrderId());

        [, $intent, $userAction, , , , , $returnUrl, $cancelUrl] = $this->requestArguments;
        $this->assertSame($expectedIntent, $intent);
        $this->assertSame(OrderRequestFactory::USER_ACTION_PAY_NOW, $userAction);
        $this->assertStringEndsWith('cl=order&fnc=finalizepaypalsession', $returnUrl);
        $this->assertStringEndsWith('cl=order&fnc=cancelpaypalsession', $cancelUrl);
    }

    public function captureStrategyProvider(): array
    {
        return [
            'capture directly' => ['directly', OrderRequest::INTENT_CAPTURE],
            'capture on delivery' => ['delivery', OrderRequest::INTENT_AUTHORIZE],
            'capture manually' => ['manually', OrderRequest::INTENT_AUTHORIZE],
        ];
    }

    public function testAcceptsTheApproveLink(): void
    {
        $paymentService = $this->createPaymentService(
            $this->createApiOrder([['href' => self::PAYER_ACTION_LINK, 'rel' => 'approve']])
        );

        $this->assertSame(
            self::PAYER_ACTION_LINK,
            $paymentService->doExecuteStandardRedirectPayment($this->prepareOrder(), oxNew(EshopModelBasket::class))
        );
    }

    public function testMissingRedirectLinkThrows(): void
    {
        $paymentService = $this->createPaymentService(
            $this->createApiOrder([['href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/x', 'rel' => 'self']])
        );

        $this->expectException(PayPalException::class);
        $paymentService->doExecuteStandardRedirectPayment($this->prepareOrder(), oxNew(EshopModelBasket::class));
    }

    public function testMissingPayPalOrderThrows(): void
    {
        $paymentService = $this->createPaymentService(new PayPalApiOrder());
        $paymentService->expects($this->never())->method('trackPayPalOrder');

        $this->expectException(PayPalException::class);
        $paymentService->doExecuteStandardRedirectPayment($this->prepareOrder(), oxNew(EshopModelBasket::class));
    }

    private function createApiOrder(array $links): PayPalApiOrder
    {
        $apiOrder = new PayPalApiOrder();
        $apiOrder->id = self::PAYPAL_ORDER_ID;
        $apiOrder->status = PayPalApiOrder::STATUS_PAYER_ACTION_REQUIRED;
        $apiOrder->links = $links;

        return $apiOrder;
    }

    /**
     * @return PaymentService&MockObject
     */
    private function createPaymentService(PayPalApiOrder $createOrderResponse, string $captureStrategy = 'directly')
    {
        $orderService = $this->createMock(Orders::class);
        $orderService->method('createOrder')->willReturn($createOrderResponse);
        $serviceFactory = $this->createMock(ServiceFactory::class);
        $serviceFactory->method('getOrderService')->willReturn($orderService);

        $orderRequestFactory = $this->createMock(OrderRequestFactory::class);
        $orderRequestFactory->method('getRequest')->willReturnCallback(function (...$arguments) {
            $this->requestArguments = $arguments;
            return new OrderRequest();
        });

        $moduleSettings = $this->createMock(ModuleSettings::class);
        $moduleSettings->method('getPayPalStandardCaptureStrategy')->willReturn($captureStrategy);

        return $this->getMockBuilder(PaymentService::class)
            ->setConstructorArgs([
                Registry::getSession(),
                $this->createMock(OrderRepository::class),
                $this->createMock(SCAValidatorInterface::class),
                $moduleSettings,
                $this->createMock(LoggerInterface::class),
                $this->createMock(OrderProcessTrackingService::class),
                $serviceFactory,
                Registry::get(PatchRequestFactory::class),
                $orderRequestFactory
            ])
            ->onlyMethods(['trackPayPalOrder'])
            ->getMock();
    }

    private function prepareOrder(): EshopModelOrder
    {
        $order = oxNew(EshopModelOrder::class);
        $order->setId(self::TEST_ORDER_ID);
        $order->assign(['oxpaymenttype' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID]);
        $order->save();
        $order->load(self::TEST_ORDER_ID);

        return $order;
    }
}
