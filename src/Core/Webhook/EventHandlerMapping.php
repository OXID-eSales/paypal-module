<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\BillingSubscriptionUpdateHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutPaymentAppovalReverseHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\MerchantOnboardingCompleteHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\MerchantPartnerConsentRevokedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCapturePendingHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureRefundedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentSaleRefundedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentSaleReversedHandler;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentSaleCompletedHandler;

class EventHandlerMapping
{
    public const MAPPING = [
        'CHECKOUT.ORDER.COMPLETED' => CheckoutOrderCompletedHandler::class,
        // CHECKOUT.ORDER.APPROVED is for us the same handle like CHECKOUT.ORDER.COMPLETED
        'CHECKOUT.ORDER.APPROVED' => CheckoutOrderApprovedHandler::class,
        'CHECKOUT.PAYMENT-APPROVAL.REVERSED' => CheckoutPaymentAppovalReverseHandler::class,
        //'MERCHANT.ONBOARDING.COMPLETED' => MerchantOnboardingCompleteHandler::class,
        //'MERCHANT.PARTNER-CONSENT.REVOKED' => MerchantPartnerConsentRevokedHandler::class,
        //'PAYMENT.CAPTURE.COMPLETED' => PaymentCaptureCompletedHandler::class,
        //'PAYMENT.CAPTURE.DENIED' => PaymentCaptureDeniedHandler::class,
        //'PAYMENT.CAPTURE.REFUNDED' => PaymentCaptureRefundedHandler::class,
        //'PAYMENT.CAPTURE.PENDING' => PaymentCapturePendingHandler::class,
        //'BILLING.SUBSCRIPTION.ACTIVATED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.RENEWED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.SUSPENDED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.CREATED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.CANCELLED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.EXPIRED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => BillingSubscriptionUpdateHandler::class,
        //'BILLING.SUBSCRIPTION.UPDATED' => BillingSubscriptionUpdateHandler::class,
        //'PAYMENT.SALE.REFUNDED' => PaymentSaleRefundedHandler::class,
        //'PAYMENT.SALE.REVERSED' => PaymentSaleReversedHandler::class,
        'PAYMENT.SALE.COMPLETED' => PaymentSaleCompletedHandler::class,
    ];
}
