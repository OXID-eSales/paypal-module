<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventRetryException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelOrder;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;
use Psr\Log\LoggerInterface;

abstract class WebhookHandlerBase
{
    use ServiceContainer;

    public const WEBHOOK_EVENT_NAME = '';

    /**
     * @throws WebhookEventException|WebhookEventRetryException
     */
    public function handle(Event $event): void
    {
        $eventPayload = $this->getEventPayload($event);

        //PayPal transaction id might not yet be tracked in database depending on payment method
        $payPalTransactionId = $this->getPayPalTransactionIdFromResource($eventPayload);

        //Depending on payment method, there might not be an order id in that result
        $payPalOrderId = $this->getPayPalOrderIdFromResource($eventPayload);

        if ($payPalOrderId !== '') {
            $order = $this->getOrderByPayPalOrderId($payPalOrderId);

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
            $this->getLogger()->log(
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
        $this->cleanUpNotFinishedOrders();
    }

    public function handleWebhookTasks(
        PayPalModelOrder $paypalOrderModel,
        string $payPalTransactionId,
        string $payPalOrderId,
        array $eventPayload,
        EshopModelOrder $order
    ): void {
        // Defense-in-depth: never process webhooks for stornoed orders.
        // This prevents a stale webhook from "healing" a cancelled order
        // or writing transaction data into an order that should stay cancelled.
        if ($order->getFieldData('oxstorno') == 1) {
            $this->getLogger()->log('warning', sprintf(
                'Webhook %s skipped for stornoed order %s (nr: %s, PayPal order: %s, PayPal txn: %s)',
                static::WEBHOOK_EVENT_NAME,
                $order->getId(),
                $order->getFieldData('oxordernr'),
                $payPalOrderId,
                $payPalTransactionId
            ));
            return;
        }

        $this->handleWebhookDelay($payPalOrderId, $eventPayload, $order);
        $paypalOrderModel->setTransactionId($payPalTransactionId);

        /** @var ?PayPalApiModelOrder $orderDetail */
        $orderDetail = $this->getPayPalOrderDetails($payPalOrderId);

        $this->updateStatus(
            $this->getStatusFromResource($eventPayload),
            $paypalOrderModel,
            $order,
            $orderDetail
        );

        $this->markShopOrderPaymentStatus($order, $payPalTransactionId);
    }

    public function cleanUpNotFinishedOrders(): void
    {
        // check for not finished orders and reset
        /** @var PayPalModelOrder $paypalOrderModel */
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

    protected function handleWebhookDelay(
        string $payPalOrderId,
        array $eventPayload,
        EshopModelOrder $order
    ): void {
        // give the frontend time to persist
        if (!$this->isMinimumWaitTimeElapsed($eventPayload)) {
            $retryDelay = $this->getWebhookRetryDelay();

            $this->getLogger()->log('debug', 'Order too fresh, requesting webhook retry', [
                'payPalOrderId' => $payPalOrderId,
                'shopOrderId' => $order->getId(),
                'orderDate' => $order->getFieldData('oxorderdate'),
                'retryAfter' => $retryDelay
            ]);

            http_response_code(503);
            header('Retry-After: ' . $retryDelay);
            exit('Order too fresh, retry later');
        }
    }

    /**
     * @throws WebhookEventRetryException
     */
    protected function getOrderByPayPalOrderId(string $payPalOrderId): EshopModelOrder
    {
        try {
            $order = $this->getOrderRepository()
                ->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch (NotFound $exception) {
            throw WebhookEventRetryException::byPayPalOrderId($payPalOrderId);
        }

        return $order;
    }

    protected function getPayPalModelOrder(
        string $shopOrderId,
        string $payPalOrderId,
        string $payPalTransactionId
    ): PayPalModelOrder {
        $paypalOrderModel = $this->getOrderRepository()
            ->paypalOrderByOrderIdAndPayPalId(
                $shopOrderId,
                $payPalOrderId,
                $payPalTransactionId
            );

        return $paypalOrderModel;
    }

    protected function updateStatus(
        string $status,
        PayPalModelOrder $paypalOrderModel,
        EshopModelOrder $order,
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

            if (!$order->isOrderPaid()) {
                $oxEmail = oxNew(Email::class);
                $oxEmail->sendPuiInfo($order, $puiPaymentDetails);
            }
        }

        $paypalOrderModel->setTransactionType(Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE);
        $paypalOrderModel->setStatus($status);
        $paypalOrderModel->save();
    }

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        $apiOrder = null;
        try {
            $checkoutOrder = PayPalSession::getCheckoutOrder();
            if (is_array($checkoutOrder) && isset($checkoutOrder['id']) && $checkoutOrder['id'] === $payPalOrderId) {
                $apiOrder = new PayPalApiModelOrder($checkoutOrder);
            } else {
                $apiOrder = Registry::get(ServiceFactory::class)
                    ->getOrderService()
                    ->showOrderDetails(
                        $payPalOrderId,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
            }
        } catch (ApiException $exception) {
            $this->getLogger()->log(
                'debug',
                'Exception during ' . static::class . '::getPayPalOrderDetails().',
                [$exception]
            );
        }
        return $apiOrder;
    }

    protected function markShopOrderPaymentStatus(EshopModelOrder $order, string $payPalTransactionId): void
    {
        $order->markOrderPaid();
        $order->setTransId($payPalTransactionId);
    }

    protected function getLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        return $logger;
    }

    protected function isMinimumWaitTimeElapsed(array $eventPayload): bool
    {
        // PayPal sendet create_time des Events
        if (!isset($eventPayload['create_time'])) {
            return false;
        }

        $eventTimestamp = strtotime($eventPayload['create_time']);
        $waitTime = $this->getWebhookRetryDelay();
        $elapsedTime = time() - $eventTimestamp;

        return $elapsedTime >= $waitTime;
    }

    protected function getWebhookRetryDelay(): int
    {
        return $this->getServiceFromContainer(
            ModuleSettings::class
        )->getWebhookRetryDelay();
    }

    abstract protected function getPayPalTransactionIdFromResource(array $eventPayload): string;

    abstract protected function getStatusFromResource(array $eventPayload): string;

    abstract protected function getPayPalOrderIdFromResource(array $eventPayload): string;
}
