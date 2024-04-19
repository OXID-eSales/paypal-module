<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

/**
 * Convenience trait to work with JSON-Data
 */
trait DataGetter
{
    public function getPaypalStringData(string $key): string
    {
        /** @var null|string $value */
        $value = $this->getFieldData($key);
        return (string)$value;
    }

    public function getPaypalFloatData(string $key): float
    {
        return (float)$this->getPaypalStringData($key);
    }

    public function getPaypalIntData(string $key): int
    {
        return (int)$this->getPaypalStringData($key);
    }

    public function getPaypalBoolData(string $key): bool
    {
        /** @var null|string $value */
        $value = $this->getFieldData($key);
        return isset($value) && $value === '1';
    }
}
