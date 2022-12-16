<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

class CheckoutOrderCompletedHandler extends WebhookHandlerBase
{
    const WEBHOOK_EVENT_NAME = 'CHECKOUT.ORDER.COMPLETED';

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        $transactionId = isset($eventPayload['purchase_units'][0]['payments']['captures'][0]) ?
            $eventPayload['purchase_units'][0]['payments']['captures'][0]['id'] : '';

        return $transactionId;
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        return isset($eventPayload['status']) ? $eventPayload['status'] : '';
    }
}
