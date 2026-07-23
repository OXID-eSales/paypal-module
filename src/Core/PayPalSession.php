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
    public static function storePayPalOrderId(string $checkoutOrderId): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_CHECKOUT_ORDER_ID,
            $checkoutOrderId
        );
    }

    /**
     * PayPal store checkoutOrderId
     *
     * @param $checkoutOrderId
     */
    public static function storePayPalOrder(array $checkoutOrder): void
    {
        self::storePayPalOrderId($checkoutOrder['id'] ?? '');
        $serialized = json_encode($checkoutOrder, JSON_THROW_ON_ERROR);
        $checkoutOrder = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
        Registry::getSession()->setVariable(
            Constants::SESSION_CHECKOUT_ORDER,
            $checkoutOrder
        );
    }

    /**
     * PayPal checkout order getter
     *
     * @return mixed
     */
    public static function getCheckoutOrder()
    {
        return Registry::getSession()->getVariable(Constants::SESSION_CHECKOUT_ORDER);
    }

    /**
     * PayPal remove checkoutOrder
     */
    public static function unsetPayPalOrder(): void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_CHECKOUT_ORDER
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

    public static function unsetPayPalSession($deleteAlsoShipping = true): void
    {
        self::unsetPayPalOrderId();
        self::unsetPayPalOrder();

        $session = Registry::getSession();
        $basket = $session->getBasket();
        if ($basket !== null) {
            $basket->setPayment();
            if ($deleteAlsoShipping) {
                $basket->setShipping();
            }
        }

        if ($deleteAlsoShipping) {
            $session->deleteVariable('sShipSet');
        }
        $session->deleteVariable('paymentid');
    }

    /**
     * PayPal store PUI-CM-Id
     *
     * @param $cmId
     */
    public static function storePayPalPuiCmId(string $cmId): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_PUI_CMID,
            $cmId
        );
    }

    public static function getPayPalPuiCmId(): string
    {
        return (string) Registry::getSession()->getVariable(
            Constants::SESSION_PUI_CMID
        );
    }

    /**
     * PayPal remove PUI-CM-Id
     */
    public static function unsetPayPalPuiCmId()
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_PUI_CMID
        );
    }

    public static function isPayPalExpressOrderActive(): bool
    {
        if (!self::getCheckoutOrderId()) {
            return false;
        }

        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        return PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID === $paymentId;
    }

    /**
     * Checks if active PayPal Order exists
     *
     * @return bool
     */
    public static function isPayPalACDCOrderActive(): bool
    {
        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        if (PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID === $paymentId) {
            return true;
        }
        return false;
    }

    /**
     * Checks if active PayPalStandard Order exists
     *
     * @return bool
     */
    public static function isPayPalStandardOrderActive(): bool
    {
        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        if (PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID === $paymentId) {
            return true;
        }
        return false;
    }

    /**
     * PayPal checkout order id getter
     *
     * @return mixed
     */
    public static function getCheckoutOrderId()
    {
        return Registry::getSession()->getVariable(Constants::SESSION_CHECKOUT_ORDER_ID);
    }

    public static function setSessionRedirectLink(string $link): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_REDIRECTLINK,
            $link
        );
    }

    public static function getSessionRedirectLink(): string
    {
        return (string) Registry::getSession()->getVariable(
            Constants::SESSION_REDIRECTLINK
        );
    }

    public static function unsetSessionRedirectLink(): void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_REDIRECTLINK
        );
    }

    public static function storeOnboardingPayload(string $payload): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_ONBOARDING_PAYLOAD,
            $payload
        );
    }

    public static function getOnboardingPayload(): ?string
    {
        return Registry::getSession()->getVariable(
            Constants::SESSION_ONBOARDING_PAYLOAD
        );
    }

    public static function unsetOnboardingSession(): void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_ONBOARDING_PAYLOAD
        );
    }

    /**
     * Records the PayPal error issue of a refused capture (e.g. TRANSACTION_REFUSED)
     * so the later storno/cancel path can classify the cancel as PAYMENT_DECLINED.
     */
    public static function storeCancelDeclineIssue(string $issue): void
    {
        Registry::getSession()->setVariable(
            Constants::SESSION_CANCEL_DECLINE_ISSUE,
            $issue
        );
    }

    public static function getCancelDeclineIssue(): string
    {
        return (string) Registry::getSession()->getVariable(
            Constants::SESSION_CANCEL_DECLINE_ISSUE
        );
    }

    public static function unsetCancelDeclineIssue(): void
    {
        Registry::getSession()->deleteVariable(
            Constants::SESSION_CANCEL_DECLINE_ISSUE
        );
    }
}
