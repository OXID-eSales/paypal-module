<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

class PaymentCaptureDeniedHandler extends PaymentCaptureCompletedHandler
{
    public const WEBHOOK_EVENT_NAME = 'PAYMENT.CAPTURE.DENIED';

    protected function markShopOrderPaymentStatus(EshopModelOrder $order, string $payPalTransactionId): void
    {
        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order->markOrderPaymentFailed();
        $order->setTransId($payPalTransactionId);
    }

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        return null;
    }
}
