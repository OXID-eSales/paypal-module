<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use PHPUnit\Framework\MockObject\MockObject;

final class PaymentCaptureDeniedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.DENIED';

    public const HANDLER_CLASS = PaymentCaptureDeniedHandler::class;

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'api_v1' => [
                'payment_capture_denied_v1.json'
            ],
            'api_v2' => [
                'payment_capture_denied_v2.json'
            ],
        ];
    }

    /**
     * @dataProvider dataProviderWebhookEvent
     */
    public function testPayPalTransactionIdWithoutPayPalOrderId(string $fixture): void
    {
        $data = $this->getRequestData($fixture);
        $resourceId = $data['resource']['id'];
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $loggerMock = $this->getPsrLoggerMock();
        /** @var MockObject $loggerMock */
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                "Not enough information to handle PAYMENT.CAPTURE.DENIED with PayPal order_id '' and " .
                "PayPal transaction id '" . $resourceId . "'"
            );
        EshopRegistry::set('logger', $loggerMock);

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_capture_denied_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage()
        );

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testPuiPaymentCaptureDenied(): void
    {
        $data = $this->getRequestData('payment_capture_denied_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];
        $transactionId = $data['resource']['id'];

        // this state is when the order is created by oxid but PayPal not yet acknowledged completed order
        $this->prepareTestData($payPalOrderId);

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        // this state is when PayPal send the order completed webhook
        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);

        // we now have two PayPal order entries
        $this->assertPayPalOrderCount($payPalOrderId, 2);

        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);

        // after CheckoutOrderCompletedHandler::handle there's one paypal order entry with status null
        // and one with status
        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->get(OrderRepository::class);
        $payPalOrder = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            $payPalOrderId,
            $transactionId
        );
        // we assert that there is an entry with status completed
        $this->assertSame('DECLINED', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('ERROR', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame('0000-00-00 00:00:00', $order->getFieldData('OXPAID'));
    }
}
