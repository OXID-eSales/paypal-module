<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;

class PaymentCaptureDeniedHandler implements HandlerInterface
{
    use WebhookHandlerTrait;

    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        /** @var EshopModelOrder $order */
        $order = $this->getOrder($event);

        $payPalOrderId = $this->getPayPalOrderId($event);
        $data = $this->getEventPayload($event)['resource'];

        $order->markOrderPaymentFailed();

        /** @var PayPalModelOrder $paypalOrderModel */
        $paypalOrderModel = $this->getServiceFromContainer(OrderRepository::class)
            ->paypalOrderByOrderIdAndPayPalId($order->getId(), $payPalOrderId);
        $paypalOrderModel->setStatus($data['state']);
        $paypalOrderModel->save();
    }
}
