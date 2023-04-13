<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

class Constants
{
    const PAYPAL_JS_SDK_URL = 'https://www.paypal.com/sdk/js';
    const PAYPAL_INTEGRATION_DATE = '2020-07-29';
    const PAYPAL_ORDER_INTENT_CAPTURE = 'CAPTURE';
    const PAYPAL_ORDER_INTENT_AUTHORIZE = 'AUTHORIZE';
    const SESSION_CHECKOUT_ORDER_ID = 'paypal-checkout-session';
    const SESSION_REDIRECTLINK = 'paypal-session-checkout-redirect';

    const SESSION_ONBOARDING_PAYLOAD = 'paypal-onboarding-payload';
    const SESSION_PUI_CMID = 'paypal-pui-cmid';
    const SESSION_ACDC_PAYPALORDER_STATUS = 'oscpaypal-acdcorder-status';

    const PAYPAL_ORDER_REFERENCE_ID = 'OXID_REFERENCE';

    const PAYPAL_ONBOARDING_SANDBOX_URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
    const PAYPAL_ONBOARDING_LIVE_URL = 'https://www.paypal.com/bizsignup/partner/entry';

    const PAYPAL_PUI_PROCESSING_INSTRUCTIONS = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
    const PAYPAL_PUI_FNPARAMS = 'fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99';
    const PAYPAL_PUI_FLOWID = 'Oxid_PayPal_PUI_Checkout';

    const PAYPAL_WAIT_FOR_WEBOOK_TIMEOUT_IN_SEC = 60;

    const PAYPAL_TRANSACTION_TYPE_CAPTURE = 'capture';
    const PAYPAL_TRANSACTION_TYPE_AUTH = 'authorization';
    const PAYPAL_TRANSACTION_TYPE_REFUND = 'refund';

    /**
     * Please note! The authorization of an order is valid for three days (1 Day = 86400 sec).
     * It will be refreshed automatically for a maximum of 29 days after ordering.
     * After that, it is no longer possible to capture the money.
     */
    const PAYPAL_DAY = 86400;
    const PAYPAL_AUTHORIZATION_VALIDITY = 3 * self::PAYPAL_DAY;
    const PAYPAL_MAXIMUM_TIME_FOR_CAPTURE = 29 * self::PAYPAL_DAY;

    // BN Codes defined together with PayPal
    const PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP = 'Oxid_Cart_Payments';
    const PAYPAL_PARTNER_ATTRIBUTION_ID_EXPRESS = 'Oxid_Cart_PymtsShortcut';
    const PAYPAL_PARTNER_ATTRIBUTION_ID_BANNER = 'oxid_Cart_Instbanners';

    //SCA contingencies parameter
    const PAYPAL_SCA_ALWAYS = 'SCA_ALWAYS';
    const PAYPAL_SCA_DISABLED = 'SCA_DISABLED';
    const PAYPAL_SCA_WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';

    const PAYPAL_STATUS_COMPLETED = 'COMPLETED';
}
