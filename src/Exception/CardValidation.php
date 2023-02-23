<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;

class CardValidation extends StandardException
{
    public static function byMissingPaymentSource(): self
    {
        return new self('Cannot find payment source.');
    }

    public static function byPaymentSource(): self
    {
        return new self('Unexpected non card payment source.');
    }

    public static function byMissingAuthenticationResult(): self
    {
        return new self('Authentication result is null, likely no SCA check was done.');
    }

    public static function byAuthenticationResult(): self
    {
        return new self('Authentication failed, plese try again.');
    }
}
