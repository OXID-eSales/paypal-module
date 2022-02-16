<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;


class CheckoutOrderCompletedHandler implements HandlerInterface
{
    use ServiceContainer;

    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        $data = $event->getData()['resource'];
        $payPalOrderId = (string) $data['id'];

        if (!$payPalOrderId) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        //get PayPalOrder
        try {
            $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
            /** @var EshopModelOrder $order */
            $order = $orderRepository->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch(NotFound $exception) {
            throw WebhookEventException::byPayPalOrderId($payPalOrderId);
        }

        //TODO: tbd: query order details from paypal. On the other hand, we just got verified that this data came from PayPal.
        if (
            $data['status'] == OrderResponse::STATUS_COMPLETED &&
            $data['purchase_units'][0]['payments']['captures'][0]['status'] == Capture::STATUS_COMPLETED
        ) {
            $order->markOrderPaid();
        }
    }
}
