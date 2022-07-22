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
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;

class CheckoutOrderApprovedHandler implements HandlerInterface
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

        $statusSet = false;
        if (!$this->isCompleted($data) && ($data->intent === Constants::PAYPAL_ORDER_INTENT_CAPTURE)) {
            //This one needs a capture
            $response = $this->capturePayment($payPalOrderId);
            if (
                $response->status == OrderResponse::STATUS_COMPLETED &&
                $response->purchase_units[0]->payments->captures[0]->status == Capture::STATUS_COMPLETED
            ) {
                $order->markOrderPaid();
                $order->setTransId($response->purchase_units[0]->payments->captures[0]->id);
                $this->setStatus($order, $response->status, $payPalOrderId);
                $statusSet = true;
            }
        }

        if (!$statusSet) {
            $this->setStatus($order, $data['status'], $payPalOrderId);
        }

        // check for not finished orders and reset
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $orderService = $serviceFactory->getOrderService();
        $orderService->cleanUpNotFinishedOrders();
    }

    /**
     * Captures payment for given order
     *
     * @param string $orderId
     *
     * @return OrderResponse
     * @throws ApiException
     */
    private function capturePayment(string $orderId): OrderResponse
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();
        $request = new OrderCaptureRequest();

        return $service->capturePaymentForOrder('', $orderId, $request, '');
    }
}
