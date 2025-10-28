<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Model\User;
use Psr\Log\LoggerInterface;

class VaultPaymentTokenCreatedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'VAULT.PAYMENT-TOKEN.CREATED';

    /** @var LoggerInterface */
    private $logger;

    /**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {
        $this->logger = $this->getLogger();
        $eventPayload = $this->getEventPayload($event);

        $customerId = $this->extractCustomerId($eventPayload);
        $user = $this->resolveUser($eventPayload);

        if (empty($user)) {
            $this->logUserNotFound();
            return;
        }

        if (empty($customerId)) {
            $this->logMissingCustomerId($eventPayload);
            return;
        }

        if ($customerId !== '' && $user instanceof User) {
            $this->processVaultToken($customerId, $user);
        }
    }

    /**
     * Extract customer ID from event payload
     */
    protected function extractCustomerId(array $eventPayload): string
    {
        return !empty($eventPayload['customer']['id']) ? $eventPayload['customer']['id'] : '';
    }

    /**
     * Resolve the user from the event payload
     *
     * @param array $eventPayload
     * @return object|null
     */
    protected function resolveUser(array $eventPayload)
    {
        $payPalOrderId = $this->extractOrderIdFromPayload($eventPayload);

        if ($payPalOrderId === '') {
            return null;
        }

        /** @var EshopModelOrder $order */
        $order = $this->getOrderByPayPalOrderId($payPalOrderId);
        $user = $order->getOrderUser();

        $this->updateTrackingId($payPalOrderId);

        return $user;
    }

    /**
     * Update tracking ID from PayPal order details
     */
    protected function updateTrackingId(string $payPalOrderId): void
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $orderService = $serviceFactory->getOrderService();
        /** @var VaultingService $vaultingService */
        $vaultingService = $serviceFactory->getVaultingService();

        $payPalOrder = $orderService->showOrderDetails(
            $payPalOrderId,
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
        );

        $customIdString = !empty($payPalOrder->purchase_units[0]->custom_id)
            ? $payPalOrder->purchase_units[0]->custom_id
            : null;

        if ($customIdString) {
            $customId = json_decode($customIdString);
            $traceId = $customId->id;
            $vaultingService->setTrackingId($traceId);
        }
    }

    /**
     * Process vault token by saving customer ID and clearing cache
     */
    protected function processVaultToken(string $customerId, User $user): void
    {
        $this->getPaymentService()->saveCustomerIdToUser($customerId, $user);

        /** @var VaultingService $vaultingService */
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $vaultingService->clearVaultedTokenCache();

        $this->logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook received customer.id field.', [
            'customerId' => $customerId,
            'userId' => $user->getId()
        ]);
    }

    /**
     * Log when user is not found
     */
    protected function logUserNotFound(): void
    {
        $this->logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook error: shop user unknown', []);
    }

    /**
     * Log when customer ID is missing
     */
    protected function logMissingCustomerId(array $eventPayload): void
    {
        $this->logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook received without customer.id field.', [
            'event_payload_keys' => array_keys($eventPayload),
        ]);
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

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        // VAULT.PAYMENT-TOKEN.CREATED is not tied to a PayPal order; return empty to skip order-bound processing
        return '';
    }

    /**
     * Extract order id from a given VAULT.PAYMENT-TOKEN.CREATED resource payload.
     */
    protected function extractOrderIdFromPayload(array $eventPayload): string
    {
        return (string)($eventPayload['metadata']['order_id'] ?? '');
    }
}
