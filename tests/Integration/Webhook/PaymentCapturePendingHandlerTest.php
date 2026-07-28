<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCapturePendingHandler;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;

final class PaymentCapturePendingHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.PENDING';

    public const HANDLER_CLASS = PaymentCapturePendingHandler::class;

    public const FIXTURE = 'payment_capture_pending_v2.json';

    /**
     * A pending capture keeps the order unpaid but must leave it usable: no ERROR
     * status, no storno, and the transaction id recorded so it can be reconciled
     * once PAYMENT.CAPTURE.COMPLETED arrives.
     */
    public function testPendingCaptureKeepsOrderUnpaidAndNotCancelled(): void
    {
        $data = $this->getRequestData(self::FIXTURE);
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];
        $transactionId = $data['resource']['id'];

        $this->prepareTestData($payPalOrderId);

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle(new WebhookEvent($data, self::WEBHOOK_EVENT));

        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->get(OrderRepository::class);
        $payPalOrder = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            $payPalOrderId,
            $transactionId
        );

        $this->assertSame('PENDING', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('NOT_FINISHED', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame('0000-00-00 00:00:00', $order->getFieldData('OXPAID'));
        $this->assertSame($transactionId, $order->getFieldData('OXTRANSID'));
        $this->assertEquals(0, $order->getFieldData('OXSTORNO'));
    }

    /**
     * PayPal does not guarantee webhook ordering, so a PENDING event delivered after
     * the capture already settled must not reset the order to unpaid.
     */
    public function testLatePendingEventDoesNotUndoSettledPayment(): void
    {
        $data = $this->getRequestData(self::FIXTURE);
        $payPalOrderId = $data['resource']['supplementary_data']['related_ids']['order_id'];

        $this->prepareTestData($payPalOrderId);

        $paidDate = '2026-07-28 13:55:00';
        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $order->assign([
            'oxtransstatus' => 'OK',
            'oxpaid' => $paidDate,
            'oxpaymenttype' => 'oscpaypal',
            'oxtransid' => 'ALREADY_SETTLED',
        ]);
        $order->save();

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle(new WebhookEvent($data, self::WEBHOOK_EVENT));

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('OK', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame($paidDate, $order->getFieldData('OXPAID'));
        $this->assertSame('ALREADY_SETTLED', $order->getFieldData('OXTRANSID'));
    }

    public function testPendingEventIsRegisteredForTheWebhookSubscription(): void
    {
        $this->assertArrayHasKey(
            self::WEBHOOK_EVENT,
            \OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping::MAPPING
        );
        $this->assertSame(
            self::HANDLER_CLASS,
            \OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping::MAPPING[self::WEBHOOK_EVENT]
        );
    }
}
