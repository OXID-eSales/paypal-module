<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;

class PaymentCaptureCompletedHandler extends WebhookHandlerBase
{
    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        /** @var EshopModelOrder $order */
        $order = $this->getOrderByTransactionId($event);

        $payPalTransactionId = $this->getPayPalId($event);
        $data = $this->getEventPayload($event)['resource'];

        //API v1 response uses 'state', v2 uses 'status'
        $status = isset($data['state']) ? $data['state'] : $data['status'];

        $this->setStatus($order, $status, '', $payPalTransactionId);
        $order->markOrderPaid();

        parent::handle($event);
    }
}
