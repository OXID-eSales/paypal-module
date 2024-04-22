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
trait TranslationDataGetter
{
    public static function getTranslatedString(string $key): string
    {
        $value = Registry::getLang()->translateString($key);
        return is_array($value) ? implode(' ', $value) : $value;
    }
}
