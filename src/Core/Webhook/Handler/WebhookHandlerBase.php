<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalLogger;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;

abstract class WebhookHandlerBase
{
    use ServiceContainer;

    const WEBHOOK_EVENT_NAME = '';

    /**
     * @inheritDoc
     * @throws WebhookEventException
     */
    public function handle(Event $event)
    {
        $eventPayload = $this->getEventPayload($event);

        //PayPal transaction id might not yet be tracked in database depending on payment method
        $payPalTransactionId = $this->getPayPalTransactionIdFromResource($eventPayload);

        //Depending on payment method, there might not be an order id in that result
        $payPalOrderId = $this->getPayPalOrderIdFromResource($eventPayload);

        if ($payPalOrderId) {
            /** @var EshopModelOrder $order */
            $order = $this->getOrderByPayPalOrderId($payPalOrderId);

            /** @var PayPalModelOrder $paypalOrderModel */
            $paypalOrderModel = $this->getPayPalModelOrder(
                (string) $order->getId(),
                $payPalOrderId,
                $payPalTransactionId
            );

            $this->handleWebhookTasks(
                $paypalOrderModel,
                $payPalTransactionId,
                $payPalOrderId,
                $eventPayload,
                $order
            );
        } else {
            $logger = new PayPalLogger();
            $logger->debug(
                "Not enough information to handle " . static::WEBHOOK_EVENT_NAME .
                " with PayPal order_id '" . $payPalOrderId . "' and PayPal transaction id '" .
                $payPalTransactionId . "'"
            );
        }

        //Webhook is used to trigger unfinished order cleanup at the end of each webhook handle.
        //TODO: check if webhook handler really is the place place for this
        $this->cleanUpNotFinishedOrders();
    }

    public function handleWebhookTasks(
        PayPalModelOrder $paypalOrderModel,
        string $payPalTransactionId,
        string $payPalOrderId,
        array $eventPayload,
        EshopModelOrder $order
    ) {
        $paypalOrderModel->setTransactionId($payPalTransactionId);

        /** @var ?PayPalApiModelOrder $orderDetail */
        $orderDetail = $this->getPayPalOrderDetails($payPalOrderId);

        $this->updateStatus(
            $this->getStatusFromResource($eventPayload),
            $paypalOrderModel,
            $orderDetail
        );

        $this->markShopOrderPaymentStatus($order, $payPalTransactionId);
    }

    public function cleanUpNotFinishedOrders()
    {
        // check for not finished orders and reset
        /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
        $this->getOrderRepository()
            ->cleanUpNotFinishedOrders();
    }

    public function getOrderRepository(): OrderRepository
    {
        return $this->getServiceFromContainer(OrderRepository::class);
    }

    public function getPaymentService(): PaymentService
    {
        return $this->getServiceFromContainer(PaymentService::class);
    }

    /**
     * @throws WebhookEventException
     */
    public function getEventPayload(Event $event): array
    {
        if (!isset($event->getData()['resource'])) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        return $event->getData()['resource'];
    }

    /**
     * @throws WebhookEventException
     */
    protected function getOrderByPayPalOrderId(string $payPalOrderId): EshopModelOrder
    {
        try {
            /** @var EshopModelOrder $order */
            $order = $this->getOrderRepository()
                ->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch (NotFound $exception) {
            throw WebhookEventException::byPayPalOrderId($payPalOrderId);
        }

        return $order;
    }

    protected function getPayPalModelOrder(
        string $shopOrderId,
        string $payPalOrderId,
        string $payPalTransactionId
    ): PayPalModelOrder {
        /** @var PayPalModelOrder $paypalOrderModel */
        $paypalOrderModel = $this->getOrderRepository()
            ->paypalOrderByOrderIdAndPayPalId(
                $shopOrderId,
                $payPalOrderId,
                $payPalTransactionId
            );

        return $paypalOrderModel;
    }

    /**
     * @return null|PayPalApiModelOrder
     */
    protected function getPayPalOrderDetails(string $payPalOrderId)
    {
        return null; //only needed for PAYMENT.CAPTURE.COMPLETED webhook event
    }

    /**
     * @param string $status
     * @param PayPalModelOrder $paypalOrderModel
     * @param null|PayPalApiModelOrder $orderDetails
     */
    protected function updateStatus(
        string $status,
        $paypalOrderModel,
        $orderDetails
    ) {
        if (
            $orderDetails &&
            ($puiPaymentDetails = $orderDetails->payment_source->pay_upon_invoice ?? null)
        ) {
            $paypalOrderModel->setPuiPaymentReference($puiPaymentDetails->payment_reference);
            $paypalOrderModel->setPuiBic($puiPaymentDetails->bic);
            $paypalOrderModel->setPuiIban($puiPaymentDetails->iban);
            $paypalOrderModel->setPuiBankName($puiPaymentDetails->bank_name);
            $paypalOrderModel->setPuiAccountHolderName($puiPaymentDetails->account_holder_name);
        }

        $paypalOrderModel->setStatus($status);
        $paypalOrderModel->save();
    }

    protected function markShopOrderPaymentStatus(EshopModelOrder $order, string $payPalTransactionId)
    {
        $order->markOrderPaid();
        $order->setTransId($payPalTransactionId);
    }

    abstract protected function getPayPalTransactionIdFromResource(array $eventPayload): string;

    abstract protected function getStatusFromResource(array $eventPayload): string;

    abstract protected function getPayPalOrderIdFromResource(array $eventPayload): string;
}
