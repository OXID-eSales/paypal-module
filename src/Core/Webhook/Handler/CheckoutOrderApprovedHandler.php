<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;

class CheckoutOrderApprovedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'CHECKOUT.ORDER.APPROVED';

    public function handleWebhookTasks(
        PayPalModelOrder $paypalOrderModel,
        string $payPalTransactionId,
        string $payPalOrderId,
        array $eventPayload,
        EshopModelOrder $order
    ): void {
        if ($this->needsCapture($eventPayload)) {
            try {
                //NOTE: capture will trigger CHECKOUT.ORDER.COMPLETED event which will mark order paid
                $this->getPaymentService()
                    ->doCapturePayPalOrder(
                        $order,
                        $payPalOrderId,
                        $paypalOrderModel->getPaymentMethodId()
                    );
                $order->setOrderNumber(); //ensure the order has a number
            } catch (\Exception $exception) {
                /** @var Logger $logger */
                $logger = $this->getServiceFromContainer(Logger::class);
                $logger->log(
                    'debug',
                    sprintf(
                        "Error during %s for PayPal order_id '%s'",
                        self::WEBHOOK_EVENT_NAME,
                        $payPalOrderId
                    ),
                    [$exception]
                );
            }
        }
    }

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        return isset($eventPayload['payments']['captures'][0]) ?
            $eventPayload['payments']['captures'][0]['id'] : '';
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        return $eventPayload['status'] ?? '';
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

        return $service->capturePaymentForOrder(
            '',
            $orderId,
            $request,
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
        );
    }

    private function needsCapture(array $eventPayload): bool
    {
        return !$this->isCompleted($eventPayload) &&
            isset($eventPayload['intent']) &&
            ($eventPayload['intent'] === Constants::PAYPAL_ORDER_INTENT_CAPTURE);
    }

    private function isCompleted(array $eventPayload): bool
    {
        $condition1 = isset(
            $eventPayload['status'],
            $eventPayload['purchase_units'][0]['payments']['captures'][0]['status']
        );
        $condition2 = $this->getStatusFromResource($eventPayload) === OrderResponse::STATUS_COMPLETED;
        $condition3 = $eventPayload['purchase_units'][0]['payments']['captures'][0]['status'] ===
            Capture::STATUS_COMPLETED;
        return ($condition1 && $condition2 && $condition3);
    }
}
