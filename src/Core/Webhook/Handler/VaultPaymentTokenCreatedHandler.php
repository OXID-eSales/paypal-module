<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use Psr\Log\LoggerInterface;

class VaultPaymentTokenCreatedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'VAULT.PAYMENT-TOKEN.CREATED';

    /**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getLogger();
        $eventPayload = $this->getEventPayload($event);
        $user = null;
        $customerId = !empty($eventPayload['customer']['id']) ? $eventPayload['customer']['id'] : '';
        $payPalOrderId = $this->extractOrderIdFromPayload($eventPayload);

        if ($payPalOrderId !== '') {
            /** @var EshopModelOrder $order */
            $order = $this->getOrderByPayPalOrderId($payPalOrderId);
            $user = $order->getOrderUser();
        }

        if (empty($user)) {
            $logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook error: shop user unknown', []);
            return;
        }

        if ($customerId !== '' && is_object($user)) {
            // Save the PayPal vault customer ID to the current user
            $this->getPaymentService()->saveCustomerIdToUser($customerId, $user);
            $logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook received customer.id field.', [
                'customerId' => $customerId,
                'userId' => $user->getId()
            ]);
        } else {
            // Log and return silently if no customer id is present
            $logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook received without customer.id field.', [
                'event_payload_keys' => array_keys($eventPayload),
            ]);
        }
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
