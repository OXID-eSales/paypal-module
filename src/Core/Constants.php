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
    public const PAYPAL_ORDER_INTENT_AUTHORIZE = 'AUTHORIZE';
    public const SESSION_CHECKOUT_ORDER_ID = 'paypal-checkout-session';
    public const SESSION_REDIRECTLINK = 'paypal-session-checkout-redirect';

    public const SESSION_ONBOARDING_PAYLOAD = 'paypal-onboarding-payload';
    public const SESSION_ONBOARDING_MERCHANTID = 'paypal-onboarding-merchantid';
    public const SESSION_PUI_CMID = 'paypal-pui-cmid';

    public const PAYPAL_ORDER_REFERENCE_ID = 'OXID_REFERENCE';

    public const PAYPAL_ONBOARDING_SANDBOX_URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
    public const PAYPAL_ONBOARDING_LIVE_URL = 'https://www.paypal.com/bizsignup/partner/entry';

    public const PAYPAL_PUI_PROCESSING_INSTRUCTIONS = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
    public const PAYPAL_PUI_FNPARAMS = 'fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99';
    public const PAYPAL_PUI_FLOWID = 'Oxid_PayPal_PUI_Checkout';

    public const PAYPAL_SESSION_TIMEOUT_IN_SEC = 600;
    /**
     * Please note! The authorization of an order is valid for three days (1 Day = 86400 sec).
     * It will be refreshed automatically for a maximum of 29 days after ordering.
     * After that, it is no longer possible to capture the money.
     */
    public const PAYPAL_DAY = 86400;
    public const PAYPAL_AUTHORIZATION_VALIDITY = 3 * self::PAYPAL_DAY;
    public const PAYPAL_MAXIMUM_TIME_FOR_CAPTURE = 29 * self::PAYPAL_DAY;

    // BN Codes defined together with PayPal
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP = 'Oxid_Cart_Payments';
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_EXPRESS = 'Oxid_Cart_PymtsShortcut';
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_BANNER = 'oxid_Cart_Instbanners';
}
