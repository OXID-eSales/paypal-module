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
    public const PAYPAL_INTEGRATION_DATE = '2024-04-26';
    public const PAYPAL_ORDER_INTENT_CAPTURE = 'CAPTURE';
    public const PAYPAL_ORDER_INTENT_AUTHORIZE = 'AUTHORIZE';
    public const SESSION_CHECKOUT_ORDER_ID = 'paypal-checkout-session';
    public const SESSION_REDIRECTLINK = 'paypal-session-checkout-redirect';

    public const SESSION_ONBOARDING_PAYLOAD = 'paypal-onboarding-payload';
    public const SESSION_PUI_CMID = 'paypal-pui-cmid';
    public const SESSION_ACDC_PAYPALORDER_STATUS = 'oscpaypal-acdcorder-status';

    public const PAYPAL_ORDER_REFERENCE_ID = 'OXID_REFERENCE';

    public const PAYPAL_ONBOARDING_SANDBOX_URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
    public const PAYPAL_ONBOARDING_LIVE_URL = 'https://www.paypal.com/bizsignup/partner/entry';

    public const PAYPAL_APPLEPAYCERT_SANDBOX_URL =
        'https://paypalobjects.com/devdoc/apple-pay/sandbox/apple-developer-merchantid-domain-association';

    public const PAYPAL_APPLEPAYCERT_LIVE_URL =
        'https://paypalobjects.com/devdoc/apple-pay/well-known/apple-developer-merchantid-domain-association';

    public const PAYPAL_PUI_PROCESSING_INSTRUCTIONS = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
    public const PAYPAL_PUI_FNPARAMS = 'fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99';
    public const PAYPAL_PUI_FLOWID = 'Oxid_PayPal_PUI_Checkout';

    public const PAYPAL_WAIT_FOR_WEBOOK_TIMEOUT_IN_SEC = 60;

    public const PAYPAL_TRANSACTION_TYPE_CAPTURE = 'capture';
    public const PAYPAL_TRANSACTION_TYPE_AUTH = 'authorization';
    public const PAYPAL_TRANSACTION_TYPE_REFUND = 'refund';

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
    // deprecated, we use only the PPCP-BN-Code since now 2023-11
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_EXPRESS = 'Oxid_Cart_PymtsShortcut';
    // deprecated, we use only the PPCP-BN-Code since now 2023-11
    public const PAYPAL_PARTNER_ATTRIBUTION_ID_BANNER = 'oxid_Cart_Instbanners';

    //SCA contingencies parameter
    public const PAYPAL_SCA_ALWAYS = 'SCA_ALWAYS';
    public const PAYPAL_SCA_DISABLED = 'SCA_DISABLED';
    public const PAYPAL_SCA_WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';

    public const PAYPAL_STATUS_COMPLETED = 'COMPLETED';
}
