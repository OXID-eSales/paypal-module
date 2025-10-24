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
use Psr\Log\LoggerInterface;

class VaultPaymentTokenCreatedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'VAULT.PAYMENT-TOKEN.CREATED';

    /**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $orderService = $serviceFactory->getOrderService();
        /** @var VaultingService $vaultingService */
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
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

            $payPalOrder = $orderService->showOrderDetails(
                $payPalOrderId,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
            $customIdString = !empty($payPalOrder->purchase_units[0]->custom_id) ? $payPalOrder->purchase_units[0]->custom_id : null;
            if($customIdString){
                $customId = json_decode($customIdString);
                $traceId = $customId->id;
                $vaultingService->setTrackingId($traceId);
            }
        }

        if (empty($user)) {
            $logger->log('debug', 'VAULT.PAYMENT-TOKEN.CREATED webhook error: shop user unknown', []);
            return;
        }

        if ($customerId !== '' && is_object($user)) {
            // Save the PayPal vault customer ID to the current user
            $this->getPaymentService()->saveCustomerIdToUser($customerId, $user);

            //Clear cached tokens
            $vaultingService->clearVaultedTokenCache();

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
