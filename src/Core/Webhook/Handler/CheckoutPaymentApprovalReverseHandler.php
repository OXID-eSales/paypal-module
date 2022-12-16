<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

class CheckoutPaymentApprovalReverseHandler extends PaymentCaptureDeniedHandler
{
    const WEBHOOK_EVENT_NAME = 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        $orderId = isset($eventPayload['order_id']) ?
            $eventPayload['order_id'] : '';

        return $orderId;
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        $status = parent::getStatusFromResource($eventPayload);

        return $status ?: self::WEBHOOK_EVENT_NAME;
    }
}
