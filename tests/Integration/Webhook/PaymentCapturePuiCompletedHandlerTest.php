<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook\WebhookHandlerBaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class PaymentCapturePuiCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.COMPLETED';

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_capture_completed_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage()
        );

        $handler = \oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);
    }

    public function testPuiPaymentCaptureCompleted(): void
    {
        $data = $this->getRequestData('payment_capture_completed_pui_v1.json');
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];
        $transactionId = $data['resource']['id'];

        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        // this state is when the order is created by oxid but PayPal not yet acknowledged completed order
        $this->prepareTestData($payPalOrderId);

        // this state is when PayPal send the order completed webhook
        $handler = \oxNew(PaymentCaptureCompletedHandler::class);
        $handler->handle($event);

        // we now have two PayPal order entries
        $this->assertPayPalOrderCount($payPalOrderId, 2);

        $payPalOrder = \oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);

        // after CheckoutOrderCompletedHandler::handle there's one paypal order entry with status null
        // and one with status completed
        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->get(OrderRepository::class);
        $payPalOrder = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            $payPalOrderId,
            $transactionId
        );
        // we assert that there is an entry with status completed
        $this->assertSame('COMPLETED', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = \oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('OK', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame($transactionId, $order->getFieldData('OXTRANSID'));
        $this->assertStringStartsWith(date('Y-m-d'), $order->getFieldData('OXPAID'));
    }
}
