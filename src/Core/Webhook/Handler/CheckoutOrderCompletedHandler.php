<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;

class CheckoutOrderCompletedHandler extends WebhookHandlerBase
{
    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        /** @var EshopModelOrder $order */
        $order = $this->getOrderByOrderId($event);

        $payPalOrderId = $this->getPayPalId($event);
        $data = $this->getEventPayload($event)['resource'];

        $this->setStatus($order, $data['status'], $payPalOrderId);
        $order->markOrderPaid();

        parent::handle($event);
    }
}
