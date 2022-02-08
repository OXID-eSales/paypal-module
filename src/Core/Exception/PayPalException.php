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

    public static function uAPMPaymentFail(): self
    {
        return new self('uAPM-Payment something is wrong');
    }
}
