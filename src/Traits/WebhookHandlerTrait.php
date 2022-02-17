<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;

trait WebhookHandlerTrait
{
    public function getOrder(Event $event): EshopModelOrder
    {
        $payPalOrderId = $this->getPayPalOrderId($event);

        //get PayPalOrder
        try {
            $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
            /** @var EshopModelOrder $order */
            $order = $orderRepository->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch(NotFound $exception) {
            throw WebhookEventException::byPayPalOrderId($payPalOrderId);
        }

        return $order;
    }

    public function getPayPalOrderId(Event $event): string
    {
        $data = $this->getEventPayload($event);

        if (!is_array($data) || !isset($data['resource'])) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        $payPalOrderId = (string) $data['resource']['id'];

        if (!$payPalOrderId) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        return $payPalOrderId;
    }

    public function getEventPayload(Event $event): array
    {
        $data = $event->getData();

        if (!is_array($data)) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        return $data;
    }
}