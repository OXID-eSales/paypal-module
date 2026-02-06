<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

class CheckoutOrderCompletedHandler extends WebhookHandlerBase
{
    public const WEBHOOK_EVENT_NAME = 'CHECKOUT.ORDER.COMPLETED';

    protected function getPayPalOrderIdFromResource(array $eventPayload): string
    {
        return (string) $eventPayload['id'];
    }

    protected function getPayPalTransactionIdFromResource(array $eventPayload): string
    {
        $transactionId = isset($eventPayload['purchase_units'][0]['payments']['captures'][0]) ?
            $eventPayload['purchase_units'][0]['payments']['captures'][0]['id'] : '';

        return $transactionId;
    }

    protected function getStatusFromResource(array $eventPayload): string
    {
        return isset($eventPayload['status']) ? $eventPayload['status'] : '';
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
                'Exception during CheckoutOrderCompletedHandler::getPayPalOrderDetails().',
                [$exception]
            );
        }

        return $apiOrder;
    }
}
