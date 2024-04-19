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
trait RequestDataGetter
{
    public static function getRequestStringParameter(string $key, bool $escaped = false): string
    {
        /** @var \OxidEsales\Eshop\Core\Request $request */
        $request = Registry::getRequest();
        $value = $escaped ? $request->getRequestEscapedParameter($key) : $request->getRequestParameter($key);
        $value = is_string($value) ? (string)$value : $value;

        if (is_string($value)){
            return $value;
        }

        return '';
    }

    public function getRequestBoolParameter(string $key): bool
    {
        /** @var \OxidEsales\Eshop\Core\Request $request */
        $request = Registry::getRequest();
        $value = $request->getRequestParameter($key);

        return isset($value) && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    public function getRequestArrayParameter(string $key, bool $escaped = false): array
    {
        /** @var \OxidEsales\Eshop\Core\Request $request */
        $request = Registry::getRequest();
        $value = $escaped ? $request->getRequestEscapedParameter($key) : $request->getRequestParameter($key);

        return is_array($value) ? (array)$value : [$value];
    }

}
