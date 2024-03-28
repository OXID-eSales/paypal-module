<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidSolutionCatalysts\PayPal\Module;

/**
 * Convenience trait to work with JSON-Data
 */
trait ModuleSettingsGetter
{
    public function getPaypalStringSetting(string $key): string
    {
        $value = $this->moduleSettingBridge->get($key, Module::MODULE_ID);
        if(is_string($value) && $value !== '') {
            return $value;
        }

        return '';
    }

/*
    public function getPaypalFloatData(string $key): float
    {
        return (float)$this->getPaypalStringData($key);
    }
*/
    public function getPaypalIntSetting(string $key): ?int
    {
        $value = $this->moduleSettingBridge->get($key, Module::MODULE_ID);
        if($value && is_int($value)){
            return (int)$value;
        }

        return null;
    }

    public function getPaypalBoolSetting(string $key): bool
    {
        $value = $this->moduleSettingBridge->get($key, Module::MODULE_ID);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}