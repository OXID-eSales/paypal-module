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
    public const PAYPAL_ORDER_REFERENCE_ID = 'OXID_REFERENCE';

    public const PAYPAL_ONBOARDING_SANDBOX_URL = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';
    public const PAYPAL_ONBOARDING_LIVE_URL = 'https://www.paypal.com/bizsignup/partner/entry';

    public const PAYPAL_PUI_PROCESSING_INSTRUCTIONS = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    /**
     * This PartnerIds are public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this PartnerIds are unencrypted, here as part
     * of this open Source Module
     */
    public const PAYPAL_OXID_PARTNER_LIVE_ID = "FULA6AY33UTA4";
    public const PAYPAL_OXID_PARTNER_SANDBOX_ID = "LRCHTG6NYPSXN";
}
