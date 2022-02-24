<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;

class UserPhone extends StandardException
{
    public static function byRequestData(): self
    {
        return new self('Phone number cannot be parsed.');
    }
}
