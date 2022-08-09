<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;

class PaymentCaptureCompletedHandler implements HandlerInterface
{
    use WebhookHandlerTrait;

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

        $this->setStatus($order, $data['status'], '',  $payPalTransactionId);
        $order->markOrderPaid();

        $this->cleanUpNotFinishedOrders();
    }
}
