<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

abstract class WebhookHandlerBase
{
    use ServiceContainer;

    public const WEBHOOK_EVENT_NAME = '';

    /**
     * @inheritDoc
     * @throws WebhookEventException
     */
    public function handle(Event $event): void
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
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log(
                'debug',
                sprintf(
                    "Not enough information to handle %s with PayPal order_id '%s' and PayPal transaction id '%s'",
                    static::WEBHOOK_EVENT_NAME,
                    $payPalOrderId,
                    $payPalTransactionId
                )
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
    ): void {
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

    public function cleanUpNotFinishedOrders(): void
    {
        // check for not finished orders and reset
        /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
        $this->getOrderRepository()->cleanUpNotFinishedOrders();
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

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        return null; //only needed for PAYMENT.CAPTURE.COMPLETED webhook event
    }

    protected function updateStatus(
        string $status,
        PayPalModelOrder $paypalOrderModel,
        ?PayPalApiModelOrder $orderDetails
    ): void {
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

        $paypalOrderModel->setTransactionType(Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE);
        $paypalOrderModel->setStatus($status);
        $paypalOrderModel->save();
    }

    protected function markShopOrderPaymentStatus(EshopModelOrder $order, string $payPalTransactionId): void
    {
        $order->markOrderPaid();
        $order->setTransId($payPalTransactionId);
    }

    abstract protected function getPayPalTransactionIdFromResource(array $eventPayload): string;

    abstract protected function getStatusFromResource(array $eventPayload): string;

    abstract protected function getPayPalOrderIdFromResource(array $eventPayload): string;
}
