<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

class Constants
{
    public const PAYPAL_JS_SDK_URL = 'https://www.paypal.com/sdk/js';
    public const PAYPAL_INTEGRATION_DATE = '2020-07-29';
    public const PAYPAL_ORDER_INTENT_CAPTURE = 'CAPTURE';
    public const SESSION_CHECKOUT_ORDER_ID = 'paypal-checkout-session';
    public const SESSION_ACDCCHECKOUT_ORDER_ID = 'paypal-acdc-checkout-session';
    public const SESSION_UAPMCHECKOUT_ORDER_ID = 'paypal-uapm-checkout-session';
    public const SESSION_UAPMCHECKOUT_PAYMENTERROR = 'paypal-uapm-checkout-error';
    public const SESSION_UAPMCHECKOUT_REDIRECTLINK = 'paypal-uapm-checkout-redirect';

    public const SESSION_ONBOARDING_PAYLOAD = 'paypal-onboarding-payload';
    public const SESSION_ONBOARDING_MERCHANTID = 'paypal-onboarding-merchantid';
    public const SESSION_ONBOARDING_MERCHANTIDINPAYPAL = 'paypal_onboarding-merchantidpaypal';

    public const PAYPAL_ORDER_REFERENCE_ID = 'OXID_REFERENCE';

    public const PAYPAL_ONBOARDING_SANDBOX_URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
    public const PAYPAL_ONBOARDING_LIVE_URL = 'https://www.paypal.com/bizsignup/partner/entry';

    public const PAYPAL_PUI_PROCESSING_INSTRUCTIONS = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    // BN Codes defined togehter with PayPal
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_SUBSCRIPTION = 'Oxid_Cart_Subscriptions';
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP = 'Oxid_Cart_Payments';
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_EXPRESS = 'Oxid_Cart_PymtsShortcut';
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_BANNER = 'oxid_Cart_Instbanners';
}
