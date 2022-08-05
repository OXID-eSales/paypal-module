<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutPaymentApprovalReverseHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;

class EventHandlerMapping
{
    public const MAPPING = [
        'PAYMENT.CAPTURE.COMPLETED' => PaymentCaptureCompletedHandler::class,
        'CHECKOUT.ORDER.COMPLETED' => CheckoutOrderCompletedHandler::class,
        'CHECKOUT.ORDER.APPROVED' => CheckoutOrderApprovedHandler::class,
        'CHECKOUT.PAYMENT-APPROVAL.REVERSED' => CheckoutPaymentApprovalReverseHandler::class,
        'PAYMENT.CAPTURE.DENIED' => PaymentCaptureDeniedHandler::class,
    ];
}
