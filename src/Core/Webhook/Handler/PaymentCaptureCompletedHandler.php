<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

class PaymentCaptureCompletedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.COMPLETED';

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return $eventPayload['supplementary_data']['related_ids']['order_id'] ?? '';
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        //API v1 response uses 'state', v2 uses 'status' and some webhook events don't come with a status
        return $eventPayload['state'] ?? ($eventPayload['status'] ?? '');
    }

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        $apiOrder = null;

        try {
            $apiOrder = Registry::get(ServiceFactory::class)
                ->getOrderService()
                ->showOrderDetails($payPalOrderId, '');
        } catch (ApiException $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Service\Logger');
            $logger->log(
                'debug',
                'Exception during PaymentCaptureCompletedHandler::getPayPalOrderDetails().',
                [$exception]
            );
        }

        return $apiOrder;
    }
}
