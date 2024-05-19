<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Convenience trait to work session getVariable
 */
trait SessionDataGetter
{
    public static function getSessionStringVariable(string $key): string
    {
        $value = Registry::getSession()->getVariable($key);
        $value = is_string($value) ? (string)$value : $value;

        if (is_string($value)) {
            return $value;
        }

        return '';
    }
}
