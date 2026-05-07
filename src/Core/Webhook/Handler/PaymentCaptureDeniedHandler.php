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
        $order->markOrderPaymentFailed();
        $order->setTransId($payPalTransactionId);
        // Cancel the shop order to release reserved stock and flag oxstorno=1.
        // Without this the order would stay in NOT_FINISHED/ERROR with stock
        // still locked even though PayPal has finally rejected the payment. (0007946)
        $order->cancelOrder();
    }

    protected function getPayPalOrderDetails(string $payPalOrderId): ?PayPalApiModelOrder
    {
        return null;
    }
}
