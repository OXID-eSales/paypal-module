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
     * Upper bound (seconds, measured against the event create_time) for asking PayPal to retry a
     * webhook whose shop order is not yet visible. Beyond it we assume the order never materialised
     * and stop retrying. Covers the finalizeOrder() commit (sub-second to seconds) with wide margin
     * while staying far below PayPal's own 25x-over-3-days retry ceiling.
     */
    protected const ORDER_NOT_FOUND_RETRY_MAX_AGE_SECONDS = 300;

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
            $order = $this->getOrderByPayPalOrderId($payPalOrderId, $eventPayload);

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

        // Capture whether the frontend had already finalized this order at the
        // moment the webhook started. Read before any status write below, so it
        // reflects the pre-webhook state. Used by the capture-completed handler
        // to decide whether a missing order-confirmation mail must be healed
        // (frontend request died after the PayPal capture, e.g. a 503). (0007981)
        $frontendHadNotFinalized = $order->getFieldData('oxtransstatus') === 'NOT_FINISHED';

        // Same idea for the transaction id: read it before markShopOrderPaymentStatus()
        // writes the one from this event, so the hook can tell whether the frontend had
        // already recorded a capture of its own.
        $transIdBeforeWebhook = (string) $order->getFieldData('oxtransid');

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

        $this->afterPaymentStatusHandled(
            $order,
            $payPalOrderId,
            $frontendHadNotFinalized,
            $transIdBeforeWebhook
        );
    }

    /**
     * Hook invoked after the shop order payment status was written. No-op in the
     * base handler; the capture-completed handler uses it to heal a missing
     * order-confirmation mail. (0007981)
     */
    protected function afterPaymentStatusHandled(
        EshopModelOrder $order,
        string $payPalOrderId,
        bool $frontendHadNotFinalized,
        string $transIdBeforeWebhook = ''
    ): void {
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

            throw WebhookEventRetryException::retry(
                sprintf('Order %s too fresh for PayPal order %s, requesting retry', $order->getId(), $payPalOrderId),
                $retryDelay
            );
        }
    }

    /**
     * @throws WebhookEventRetryException
     */
    protected function getOrderByPayPalOrderId(string $payPalOrderId, array $eventPayload = []): EshopModelOrder
    {
        try {
            $order = $this->getOrderRepository()
                ->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch (NotFound $exception) {
            // The shop order may still be inside the finalizeOrder() DB transaction (created but
            // not yet committed, see Model/Order::finalizeOrder()), so it is invisible on this
            // separate webhook connection. Ask PayPal to retry (the caller maps a retryable
            // exception to HTTP 503 + Retry-After) rather than acknowledging with 200 — by the
            // retry the transaction has committed and the lookup succeeds. Give up (non-retryable
            // -> 200) once the event exceeds the retry cap, which means the order genuinely never
            // materialised (e.g. an abandoned express checkout).
            $retryAfter = $this->resolveNotFoundRetryAfter($eventPayload);
            if ($retryAfter !== null) {
                throw WebhookEventRetryException::retry(
                    sprintf("Shop Order for PayPal order '%s' not yet visible, requesting retry", $payPalOrderId),
                    $retryAfter
                );
            }

            throw WebhookEventRetryException::byPayPalOrderId($payPalOrderId);
        }

        return $order;
    }

    /**
     * Decides whether a not-yet-visible order should trigger a PayPal retry: returns the Retry-After
     * (seconds) while still within the retry window, or null once we should give up (respond 200).
     *
     * The cap is measured against the event's create_time. PayPal resends the same event with an
     * unchanged create_time on every retry, so (now - create_time) grows monotonically across
     * deliveries — the cap is deterministic per event, with no guessing whether the order is ready.
     * A missing create_time or an event older than the cap means the order never materialised.
     */
    protected function resolveNotFoundRetryAfter(array $eventPayload): ?int
    {
        $createTime = isset($eventPayload['create_time'])
            ? strtotime((string)$eventPayload['create_time'])
            : false;

        if ($createTime === false) {
            return null;
        }

        if ((time() - $createTime) >= self::ORDER_NOT_FOUND_RETRY_MAX_AGE_SECONDS) {
            return null;
        }

        return $this->getWebhookRetryDelay();
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

    /** @var array<string, ?PayPalApiModelOrder> */
    private static array $orderDetailsCache = [];

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        if (isset(self::$orderDetailsCache[$payPalOrderId])) {
            return self::$orderDetailsCache[$payPalOrderId];
        }

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

        self::$orderDetailsCache[$payPalOrderId] = $apiOrder;
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
        // PayPal sends create_time on the event. If the resource carries no create_time we cannot
        // determine freshness — do NOT block processing: returning false here would treat the event
        // as perpetually "too fresh" and, with the retry mechanism, loop 503 until PayPal gives up.
        // Proceed instead; a not-yet-visible order is still covered by the not-found retry path.
        if (!isset($eventPayload['create_time'])) {
            return true;
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
