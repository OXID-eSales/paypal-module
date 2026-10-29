<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;

class PayPalException extends StandardException
{
    public static function createPayPalOrderFail(): self
    {
        return new self('Could not create PayPal order.');
    }

    public static function sessionPaymentMalformedResponse(): self
    {
        return new self('Session-Payment reponse structure is not as expected.');
    }

    public static function sessionPaymentMissingRedirectLink(): self
    {
        return new self('Session-Payment reponse contains no redirect link.');
    }

    public static function sessionPaymentFail(string $details = ''): self
    {
        return new self(
            sprintf(
                'Session-Payment something is wrong: %s',
                $details
            )
        );
    }

    public static function cannotFinalizeOrderAfterExternalPaymentSuccess(string $payPalOrderId): self
    {
        return new self(
            sprintf(
                'uAPM-Payment error. We might have PayPal order %s with incomplete shop order',
                $payPalOrderId
            )
        );
    }

    public static function cannotFinalizeOrderAfterExternalPayment(string $payPalOrderId, string $paymentId): self
    {
        return new self(
            sprintf(
                'Error during external payment order finalization. ' .
                       'We might have PayPal order %s with incomplete shop order and non PayPal payment method %s',
                $payPalOrderId,
                $paymentId
            )
        );
    }

    public static function basketNotCalculated(float $positionsTotal, float $calculatedTotal): self
    {
        return new self(
            sprintf(
                'The basket positions (%.2f) do not match the products total of the last basket'
                . ' calculation (%.2f) - a position was added or removed without recalculating'
                . ' the basket. No PayPal order is created from it.',
                $positionsTotal,
                $calculatedTotal
            )
        );
    }
}
