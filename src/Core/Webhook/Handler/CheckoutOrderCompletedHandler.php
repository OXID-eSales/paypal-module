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
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;

class CheckoutOrderCompletedHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        $data = $event->getData()['resource'];

        $payPalOrderId = $data['id'] ?? '';
        $oxidOrderId = $data['purchase_units'][0]['custom_id'] ?? '';

        if (!$oxidOrderId || !$payPalOrderId) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        $order = oxNew(EshopModelOrder::class);
        if (!$order->load($oxidOrderId)) {
            throw WebhookEventException::byOrderId($oxidOrderId);
        }

        $response = $this->capturePayment($payPalOrderId);

        if (
            $response->status == OrderResponse::STATUS_COMPLETED &&
            $response->purchase_units[0]->payments->captures[0]->status == Capture::STATUS_COMPLETED
        ) {
            $order->markOrderPaid();
        }
    }

    /**
     * Captures payment for given order
     *
     * @param string $orderId
     *
     * @return Order
     * @throws ApiException
     */
    private function capturePayment(string $orderId): Order
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();
        $request = new OrderCaptureRequest();

        return $service->capturePaymentForOrder('', $orderId, $request, '');
    }
}
