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
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;

class CheckoutOrderCompletedHandler implements HandlerInterface
{
    use WebhookHandlerTrait;

    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        /** @var \OxidEsales\Eshop\Application\Model\Order $order */
        $order = $this->getOrder($event);

        $payPalOrderId = $this->getPayPalOrderId($event);
        $data = $this->getEventPayload($event)['resource'];

        //TODO: tbd: query order details from paypal. On the other hand,
        // we just got verified that this data came from PayPal.
        if (
            $data['status'] == OrderResponse::STATUS_COMPLETED &&
            $data['purchase_units'][0]['payments']['captures'][0]['status'] == Capture::STATUS_COMPLETED
        ) {
            $order->markOrderPaid();

            /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
            $paypalOrderModel = $this->getServiceFromContainer(OrderRepository::class)
                ->paypalOrderByOrderIdAndPayPalId($order->getId(), $payPalOrderId);
            $paypalOrderModel->setStatus($data['status']);
            $paypalOrderModel->save();
        }
    }
}
