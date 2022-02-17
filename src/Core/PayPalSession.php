<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;

class PayPalSession
{
    /**
     * PayPal store checkoutOrderId
     *
     * @param $checkoutOrderId
     */
    public static function storePayPalOrderId(string $checkoutOrderId, string $target = Constants::SESSION_CHECKOUT_ORDER_ID): void
    {
        Registry::getSession()->setVariable(
            $target,
            $checkoutOrderId
        );
    }

    /**
     * PayPal remove checkoutOrderId
     */
    public static function unsetPayPalOrderId(string $target = Constants::SESSION_CHECKOUT_ORDER_ID)
    {
        Registry::getSession()->deleteVariable(
            $target
        );
    }

    /**
     * Checks if active PayPal Order exists
     *
     * @return bool
     */
    public static function isPayPalOrderActive(): bool
    {
        if (!self::getcheckoutOrderId()) {
            return false;
        }

        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        if (PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID !== $paymentId) {
            return false;
        }

        return true;
    }

    /**
     * PayPal checkout order id getter
     *
     * @return mixed
     */
    public static function getcheckoutOrderId()
    {
        return Registry::getSession()->getVariable(Constants::SESSION_CHECKOUT_ORDER_ID);
    }

    /**
     * PayPal uapm checkout order id getter
     *
     * @return mixed
     */
    public static function getUapmCheckoutOrderId()
    {
        return Registry::getSession()->getVariable(Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
    }

    /**
     * PayPal uapm checkout order id getter
     *
     * @return mixed
     */
    public static function getAcdcCheckoutOrderId()
    {
        return Registry::getSession()->getVariable(Constants::SESSION_ACDCCHECKOUT_ORDER_ID);
    }

    public static function subscriptionIsProcessing(): void
    {
        Registry::getSession()->setVariable('SessionIsProcessing', true);
    }

    public static function subscriptionIsDoneProcessing(): void
    {
        $session = Registry::getSession();
        $session->deleteVariable('SessionIsProcessing');
        $session->deleteVariable('subscriptionProductOrderId');
    }

    public static function isSubscriptionProcessing(): bool
    {
        $isSubscriptionProcessing = Registry::getSession()->getVariable('SessionIsProcessing');
        return empty($isSubscriptionProcessing) ? false : true;
    }

    public static function setUapmSessionError(string $message): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_UAPMCHECKOUT_PAYMENTERROR,
            $message
        );
    }

    public static function getUapmSessionError(): ?string
    {
        return Registry::getSession()->getVariable(
            Constants::SESSION_UAPMCHECKOUT_PAYMENTERROR
        );
    }

    public static function unsetUapmSessionError():void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_UAPMCHECKOUT_PAYMENTERROR
        );
    }

    public static function setUapmRedirectLink(string $link): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_UAPMCHECKOUT_REDIRECTLINK,
            $link
        );
    }

    public static function getUapmRedirectLink(): string
    {
        return (string) Registry::getSession()->getVariable(
            Constants::SESSION_UAPMCHECKOUT_REDIRECTLINK
        );
    }

    public static function unsetUapmRedirectLink():void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_UAPMCHECKOUT_REDIRECTLINK
        );
    }
}
