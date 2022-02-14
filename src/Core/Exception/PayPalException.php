<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;

class PayPalException extends StandardException
{
    public static function createPayPalOrderFail(): self
    {
        return new self('Could not create PayPal order.');
    }

    public static function uAPMPaymentMalformedResponse(): self
    {
        return new self('uAPM-Payment reponse structure  is not as expected.');
    }

    public static function uAPMPaymentMissingRedirectLink(): self
    {
        return new self('uAPM-Payment reponse contains no redirect link.');
    }

    public static function uAPMPaymentFail(): self
    {
        return new self('uAPM-Payment something is wrong');
    }

    public static function cannotFinalizeOrderAfterExternalPaymentSuccess(string $payPalOrderId): self
    {
        return new self(sprintf('uAPM-Payment error. We might have PayPal order %s with incomplete shop order', $payPalOrderId));
    }
}
