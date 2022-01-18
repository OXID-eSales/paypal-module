<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Core\Webhook;

use OxidProfessionalServices\PayPal\Core\Webhook\Handler\BillingSubscriptionUpdateHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\MerchantOnboardingCompleteHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\MerchantPartnerConsentRevokedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentCaptureCompletedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentCaptureDeniedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentCapturePendingHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentCaptureRefundedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentSaleRefundedHandler;
use OxidProfessionalServices\PayPal\Core\Webhook\Handler\PaymentSaleReversedHandler;

class EventHandlerMapping
{
    public const MAPPING = [
        'CHECKOUT.ORDER.COMPLETED' => CheckoutOrderCompletedHandler::class,
        'MERCHANT.ONBOARDING.COMPLETED' => MerchantOnboardingCompleteHandler::class,
        'MERCHANT.PARTNER-CONSENT.REVOKED' => MerchantPartnerConsentRevokedHandler::class,
        'PAYMENT.CAPTURE.COMPLETED' => PaymentCaptureCompletedHandler::class,
        'PAYMENT.CAPTURE.DENIED' => PaymentCaptureDeniedHandler::class,
        'PAYMENT.CAPTURE.REFUNDED' => PaymentCaptureRefundedHandler::class,
        'PAYMENT.CAPTURE.PENDING' => PaymentCapturePendingHandler::class,
        'BILLING.SUBSCRIPTION.ACTIVATED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.RENEWED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.SUSPENDED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.CREATED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.CANCELLED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.EXPIRED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => BillingSubscriptionUpdateHandler::class,
        'BILLING.SUBSCRIPTION.UPDATED' => BillingSubscriptionUpdateHandler::class,
        'PAYMENT.SALE.REFUNDED' => PaymentSaleRefundedHandler::class,
        'PAYMENT.SALE.REVERSED' => PaymentSaleReversedHandler::class,
        'PAYMENT.SALE.COMPLETED' => PaymentSaleCompletedHandler::class,
    ];
}
