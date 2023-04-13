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
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;

final class PaymentCaptureCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.COMPLETED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'api_v1' => [
                'payment_capture_completed_v1.json'
            ],
            'api_v2' => [
                'payment_capture_completed_v2.json'
            ]
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
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                "Not enough information to handle PAYMENT.CAPTURE.COMPLETED with PayPal order_id '' and " .
                "PayPal transaction id '" . $resourceId . "'"
            );

        EshopRegistry::set('logger', $loggerMock);

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_capture_completed_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage()
        );

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function testPuiPaymentCaptureCompleted(): void
    {
        $data = $this->getRequestData('payment_capture_completed_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];
        $transactionId = $data['resource']['id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->prepareTestData($payPalOrderId);

        $handler = oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId);

        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);
        $this->assertSame('COMPLETED', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('OK', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame($transactionId, $order->getFieldData('OXTRANSID'));
        $this->assertStringStartsWith(date('Y-m-d'), $order->getFieldData('OXPAID'));
    }
}
