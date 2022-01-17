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
    public static function storePayPalOrderId($checkoutOrderId): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_CHECKOUT_ORDER_ID,
            $checkoutOrderId
        );
    }

    /**
     * PayPal remove checkoutOrderId
     */
    public static function unsetPayPalOrderId()
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_CHECKOUT_ORDER_ID
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
}
