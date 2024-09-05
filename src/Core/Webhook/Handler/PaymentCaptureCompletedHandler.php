<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalLogger;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

class PaymentCaptureCompletedHandler extends WebhookHandlerBase
{
    const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.COMPLETED';

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        $orderId = $eventPayload['supplementary_data']['related_ids']['order_id'] ?? '';

        return $orderId;
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

    /**
     * @return null|PayPalApiModelOrder
     */
    protected function getPayPalOrderDetails(string $payPalOrderId)
    {
        $apiOrder = null;

        try {
            $apiOrder = Registry::get(ServiceFactory::class)
                ->getOrderService()
                ->showOrderDetails(
                    $payPalOrderId,
                    '',
                    Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                );
        } catch (ApiException $exception) {
            $logger = new PayPalLogger();
            $logger->debug(
                'Exception during PaymentCaptureCompletedHandler::getPayPalOrderDetails().',
                [$exception]
            );
        }

        return $apiOrder;
    }
}
