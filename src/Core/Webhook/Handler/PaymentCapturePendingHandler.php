<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

/**
 * PayPal accepted the capture but has not settled it yet (e.g. status_details.reason
 * UNILATERAL — the payee address is not confirmed on the receiving account — or
 * PENDING_REVIEW). The funds are committed but not collected, so the shop order must
 * stay unpaid without being cancelled: PAYMENT.CAPTURE.COMPLETED or
 * PAYMENT.CAPTURE.DENIED decides the final outcome later.
 *
 * Without this handler such a capture produced no shop-side state at all whenever the
 * customer's browser never completed the capture request, leaving the order in the
 * hands of the not-finished cleanup.
 */
class PaymentCapturePendingHandler extends PaymentCaptureCompletedHandler
{
    public const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.PENDING';

    protected function markShopOrderPaymentStatus(EshopModelOrder $order, string $payPalTransactionId): void
    {
        // A late/duplicate PENDING event must never undo a payment that has settled
        // in the meantime — PayPal does not guarantee webhook ordering.
        if ($order->isOrderSuccessfullyPaid()) {
            $this->getLogger()->log('debug', sprintf(
                'Webhook %s skipped for already paid order %s (nr: %s)',
                static::WEBHOOK_EVENT_NAME,
                $order->getId(),
                $order->getFieldData('oxordernr')
            ));
            return;
        }

        $order->markOrderPaymentNotFinished();
        if ($payPalTransactionId !== '') {
            $order->setTransId($payPalTransactionId);
        }

        $this->getLogger()->log('warning', sprintf(
            'Webhook %s: capture %s for order %s (nr: %s) is pending at PayPal - order kept unpaid,'
            . ' waiting for PAYMENT.CAPTURE.COMPLETED',
            static::WEBHOOK_EVENT_NAME,
            $payPalTransactionId,
            $order->getId(),
            $order->getFieldData('oxordernr')
        ));
    }

    /**
     * No order details needed: the pending state is fully described by the event
     * payload, so spare the extra API round trip (same rationale as the denied handler).
     */
    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        return null;
    }
}
