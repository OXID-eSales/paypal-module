<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;

class PaymentCaptureDeniedHandler implements HandlerInterface
{
    use WebhookHandlerTrait;

    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        /** @var \OxidEsales\Eshop\Application\Model\Order $order */
        $order = $this->getOrderByTransactionId($event);

        $payPalTransactionId = $this->getPayPalId($event);
        $data = $this->getEventPayload($event)['resource'];

        //API v1 response uses 'state', v2 uses 'status'
        $status = isset($data['state']) ? $data['state'] : $data['status'];

        $this->setStatus($order, (string) $status, '', $payPalTransactionId);
        $order->markOrderPaymentFailed();
    }
}
