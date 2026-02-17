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
        $this->handleWebhookDelay($payPalOrderId, $eventPayload, $order);
        if ($this->needsCapture($eventPayload)) {
            try {
                //NOTE: capture will trigger CHECKOUT.ORDER.COMPLETED event which will mark order paid
                $this->getPaymentService()
                    ->doCapturePayPalOrder(
                        $order,
                        $payPalOrderId,
                        $paypalOrderModel->getPaymentMethodId()
                    );
                $order->setOrderNumber(); //ensure the order has a number
            } catch (Exception $exception) {
                $this->getLogger()->log(
                    'debug',
                    sprintf(
                        "Error during %s for PayPal order_id '%s'",
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
