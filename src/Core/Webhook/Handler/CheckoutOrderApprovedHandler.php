<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;

class CheckoutOrderApprovedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'CHECKOUT.ORDER.APPROVED';

    public function handleWebhookTasks(
        PayPalModelOrder $paypalOrderModel,
        string $payPalTransactionId,
        string $payPalOrderId,
        array $eventPayload,
        EshopModelOrder $order
    ): void {
        // Defense-in-depth: never process webhooks for stornoed orders.
        if ($order->getFieldData('oxstorno') == 1) {
            $this->getLogger()->log('warning', sprintf(
                'Webhook %s skipped for stornoed order %s (nr: %s, PayPal order: %s)',
                static::WEBHOOK_EVENT_NAME,
                $order->getId(),
                $order->getFieldData('oxordernr'),
                $payPalOrderId
            ));
            return;
        }

        // Skip capture if the order was already captured (e.g. by the frontend
        // or by a different PayPal order that replaced this one).
        if (!empty($order->getFieldData('oxtransid'))) {
            $this->getLogger()->log('debug', sprintf(
                'Webhook %s skipped capture for order %s (nr: %s) - already has transid %s',
                static::WEBHOOK_EVENT_NAME,
                $order->getId(),
                $order->getFieldData('oxordernr'),
                $order->getFieldData('oxtransid')
            ));
            return;
        }

        $this->handleWebhookDelay($payPalOrderId, $eventPayload, $order);

        if ($this->needsCapture($eventPayload)) {
            // Fetch live order details to check if already captured and to
            // pass them to doCapturePayPalOrder() (avoids redundant API GET).
            $orderDetails = $this->getPayPalOrderDetails($payPalOrderId);
            if ($orderDetails && $orderDetails->status === OrderResponse::STATUS_COMPLETED) {
                $this->getLogger()->log('debug', sprintf(
                    'Webhook %s: PayPal order %s already COMPLETED, skipping capture',
                    static::WEBHOOK_EVENT_NAME,
                    $payPalOrderId
                ));
                return;
            }

            try {
                //NOTE: capture will trigger CHECKOUT.ORDER.COMPLETED event which will mark order paid
                $this->getPaymentService()
                    ->doCapturePayPalOrder(
                        $order,
                        $payPalOrderId,
                        $paypalOrderModel->getPaymentMethodId(),
                        $orderDetails
                    );
                $order->setOrderNumber(); //ensure the order has a number
            } catch (Exception $exception) {
                $this->getLogger()->log(
                    'warning',
                    sprintf(
                        "Capture during %s failed for PayPal order_id '%s' — webhook retry will follow",
                        self::WEBHOOK_EVENT_NAME,
                        $payPalOrderId
                    ),
                    [$exception]
                );
            }
        }
    }

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        return isset($eventPayload['payments']['captures'][0]) ?
            $eventPayload['payments']['captures'][0]['id'] : '';
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        return $eventPayload['status'] ?? '';
    }

    private function needsCapture(array $eventPayload): bool
    {
        return !$this->isCompleted($eventPayload) &&
            isset($eventPayload['intent']) &&
            ($eventPayload['intent'] === Constants::PAYPAL_ORDER_INTENT_CAPTURE);
    }

    private function isCompleted(array $eventPayload): bool
    {
        $condition1 = isset(
            $eventPayload['status'],
            $eventPayload['purchase_units'][0]['payments']['captures'][0]['status']
        );
        $condition2 = $this->getStatusFromResource($eventPayload) === OrderResponse::STATUS_COMPLETED;
        $condition3 = $eventPayload['purchase_units'][0]['payments']['captures'][0]['status'] ===
            Capture::STATUS_COMPLETED;
        return ($condition1 && $condition2 && $condition3);
    }
}
