<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;

class PaymentCaptureRefundedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.REFUNDED';

    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        $eventPayload = $this->getEventPayload($event);

        //NOTE: it is the capture (transaction) id, not the order we get from up link
        $transactionId = $this->getPayPalOrderIdFromResource($eventPayload);

        /** @var EshopModelOrder $order */
        $order = $this->getOrderByPayPalTransactionId($transactionId);

        //track the refund
        $this->getPaymentService()
            ->trackPayPalOrder(
                $order->getId(),
                $this->getPayPalOrderIdByShopOrderId($order->getId()),
                (string) $order->getFieldData('oxpaymenttype'),
                $this->getStatusFromResource($eventPayload),
                $this->getPayPalTransactionIdFromResource($eventPayload),
                Constants::PAYPAL_TRANSACTION_TYPE_REFUND
            );

        //mark the original capture transaction as (partially) refunded
        $status = $order->getTotalOrderSum() > $this->getAmountFromResource($eventPayload) ?
            'PARTIALLY_REFUNDED' : 'REFUNDED';
        $this->getPaymentService()
            ->trackPayPalOrder(
                $order->getId(),
                $this->getPayPalOrderIdByShopOrderId($order->getId()),
                (string)$order->getFieldData('oxpaymenttype'),
                $status,
                $transactionId,
                Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
            );
    }

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        $links = is_array($eventPayload['links']) ? $eventPayload['links'] : [];
        $payPalOrderId = '';

        foreach ($links as $link) {
            if ($link['rel'] === 'up') {
                preg_match("/(v2\/payments\/captures\/)(.*)/", (string) $link['href'], $matches);
                $payPalOrderId = isset($matches[2]) ? $matches[2] : '';
            }
        }

        return $payPalOrderId;
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        $transactionId = isset($eventPayload['id']) ? $eventPayload['id'] : '';

        return $transactionId;
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        return isset($eventPayload['status']) ? $eventPayload['status'] : '';
    }

    protected function getAmountFromResource(array $eventPayload): float
    {
        return isset($eventPayload['amount']['value']) ? $eventPayload['amount']['value'] : 0;
    }

    /**
     * @throws WebhookEventException
     */
    protected function getOrderByPayPalTransactionId(string $captureId): EshopModelOrder
    {
        try {
            /** @var EshopModelOrder $order */
            $order = $this->getOrderRepository()
                ->getShopOrderByPayPalTransactionId($captureId);
        } catch (NotFound $exception) {
            throw WebhookEventException::byPayPalTransactionId($captureId);
        }

        return $order;
    }

    protected function getPayPalOrderIdByShopOrderId(string $shopOrderId): string
    {
        try {
            /** @var EshopModelOrder $order */
            $orderId = $this->getOrderRepository()
                ->getPayPalOrderIdByShopOrderId($shopOrderId);
        } catch (NotFound $exception) {
            throw WebhookEventException::byOrderId($shopOrderId);
        }

        return $orderId;
    }
}
