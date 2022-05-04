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

    public static function sessionPaymentFail(): self
    {
        return new self('Session-Payment something is wrong');
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
}
