<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;

class PaymentCaptureCompletedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.COMPLETED';

    /**
     * Grace period after order creation during which the frontend / redirect
     * finalize (including the 0007941 healing-mail branch) is assumed to still
     * be responsible for the order-confirmation mail. Only orders older than
     * this are healed from the webhook. Matches the proven 10-minute gate. (0007981)
     */
    protected const MAIL_HEAL_GRACE_SEC = 600;

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return $eventPayload['supplementary_data']['related_ids']['order_id'] ?? '';
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        //API v1 response uses 'state', v2 uses 'status' and some webhook events don't come with a status
        return $eventPayload['state'] ?? ($eventPayload['status'] ?? '');
    }

    /**
     * Heal the order-confirmation mail when the frontend captured the payment
     * but died (e.g. a 503 on the captureOrder AJAX request) before dispatching
     * PayPalOrderCompletedEvent, so neither the shop nor the customer mail was
     * ever sent. The webhook has marked the order paid by now; here we re-send
     * the confirmation from the persisted order. (0007981)
     *
     * Note: PayPal fires exactly one PAYMENT.CAPTURE.COMPLETED per capture and
     * retries only sequentially after a non-2xx response, so the read-gate below
     * plus the grace period are sufficient to avoid a duplicate mail without a
     * dedicated "mail sent" marker. Known limitation of the marker-less approach:
     * a customer who returns via a redirect flow more than the grace period after
     * a webhook heal can still trigger the finalizeOrderAfterExternalPayment
     * healing-mail branch, producing a second mail. This is an accepted residual.
     */
    protected function afterPaymentStatusHandled(
        EshopModelOrder $order,
        string $payPalOrderId,
        bool $frontendHadNotFinalized
    ): void {
        // Frontend already finalized -> the confirmation mail was already sent.
        if (!$frontendHadNotFinalized) {
            return;
        }

        // Only heal genuinely paid orders (never denied/failed/cancelled). The
        // PaymentCaptureDeniedHandler inherits this hook but its order is stornoed
        // and not successfully paid, so it is filtered out here.
        if ($order->getFieldData('oxstorno') == 1 || !$order->isOrderSuccessfullyPaid()) {
            return;
        }

        // Grace period: within it the frontend / redirect finalize is assumed to
        // still be responsible for the mail; do not race it from the webhook.
        $orderTimestamp = strtotime((string) $order->getFieldData('oxorderdate'));
        if ($orderTimestamp === false || $orderTimestamp > (time() - static::MAIL_HEAL_GRACE_SEC)) {
            return;
        }

        $this->healOrderConfirmationMail($order, $payPalOrderId);
    }

    /**
     * Rebuilds the basket + user from the persisted order and sends the
     * order-confirmation mail through the regular voucher-binding path. (0007981)
     */
    protected function healOrderConfirmationMail(EshopModelOrder $order, string $payPalOrderId): void
    {
        try {
            // Both methods live on the PayPal Order model mixin.
            if (
                !method_exists($order, 'recreateBasketFromOrder')
                || !method_exists($order, 'sendPayPalOrderByEmailWithVoucherBinding')
            ) {
                return;
            }

            $user = $order->getOrderUser();
            if (!$user) {
                $this->getLogger()->log('warning', sprintf(
                    'PayPal webhook heal: cannot resolve order user for order %s (PayPal order: %s), '
                    . 'confirmation mail skipped',
                    $order->getId(),
                    $payPalOrderId
                ));
                return;
            }

            $basket = $order->recreateBasketFromOrder();
            $order->sendPayPalOrderByEmailWithVoucherBinding($user, $basket);

            $this->getLogger()->log('warning', sprintf(
                'PayPal webhook heal: sent missing order-confirmation mail for order %s (nr: %s, PayPal order: %s)',
                $order->getId(),
                $order->getFieldData('oxordernr'),
                $payPalOrderId
            ));
        } catch (\Throwable $exception) {
            // Never let a mail failure break webhook processing.
            $this->getLogger()->log('warning', sprintf(
                'PayPal webhook heal: failed to send confirmation mail for order %s: %s',
                $order->getId(),
                $exception->getMessage()
            ));
        }
    }
}
