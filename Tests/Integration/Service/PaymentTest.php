<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as ApiOrderService;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;

final class PaymentTest extends BaseTestCase
{
    protected const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    protected const TEST_PRODUCT_ID = 'dc5ffdf380e15674b56dd562a7cb6aec';

    public function testCreatePayPalOrder(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $result = $paymentService->doCreatePayPalOrder($basket, OrderRequest::INTENT_CAPTURE);

        $this->assertNotEmpty($result->id);
    }

    public function testCreatePuiPayPalOrder(): void
    {
        $this->setRequestParameter('pui_required_birthdate_day', 1);
        $this->setRequestParameter('pui_required_birthdate_month', 4);
        $this->setRequestParameter('pui_required_birthdate_year', 2000);
        $this->setRequestParameter('pui_required_phonenumber', '040 111222333');

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->never())
            ->method('error');
        EshopRegistry::set('logger', $loggerMock);

        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        $transactionId = EshopRegistry::getUtilsObject()->generateUId();
        $order = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->any())
            ->method('getShopId')
            ->willReturn(1);
        $order->expects($this->any())
            ->method('getId')
            ->willReturn($transactionId);
        $order->expects($this->once())
            ->method('savePuiInvoiceNr');

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $result = $paymentService->doExecutePuiPayment($order, $basket);

        $this->assertTrue($result);
    }

    public function testSandboxAccountCanCreatePuiOrder(): void
    {
        /** @var ApiOrderService $orderService */
        $orderService = EshopRegistry::get(ServiceFactory::class)
            ->getOrderService();

        $result = $orderService->createOrder(
            $this->getPuiOrderRequest(),
            '',
            'test-' . microtime(),
            'return=minimal',
            'request-id-' . microtime()
        );

        $this->assertNotEmpty($result->id);
    }

    private function getPuiOrderRequest(): OrderRequest
    {
        $decoded = $this->getPuiRequestData();
        $request = new OrderRequest();

        $request->intent = OrderRequest::INTENT_CAPTURE;
        $request->purchase_units = $decoded['purchase_units'];
        $request->application_context = $decoded['application_context'];
        $request->payment_source = $decoded['payment_source'];
        $request->processing_instruction = "ORDER_COMPLETE_ON_PAYMENT_APPROVAL";

        return $request;
    }

    private function getPuiRequestData(): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/pui_order_request.json');

        return json_decode($json, true);
    }
}
