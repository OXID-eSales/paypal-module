<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

/**
 * Single point of money-value serialization for outbound PayPal API payloads.
 *
 * Centralized here so the fourth `number_format` argument (thousands separator)
 * stays empty in exactly one place — passing `null` there falls back to the
 * PHP default `","` in PHP 8.1+ and PayPal rejects the resulting "5,326.00"
 * with INVALID_PARAMETER_SYNTAX. See bug 0007929.
 */
class AmountFormatter
{
    public const DEFAULT_DECIMALS = 2;

    /**
     * Format a monetary value for PayPal API payloads ("5326.00").
     *
     * - Dot as decimal separator
     * - No thousands separator
     */
    public static function format(float $value, int $decimals = self::DEFAULT_DECIMALS): string
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Format a monetary value as a digit-only string in the lowest denomination
     * ("532600" for 5326.00 EUR — e.g. for currency-API call sites that expect
     * cents/minor-unit integers).
     */
    public static function formatCents(float $value, int $decimals = self::DEFAULT_DECIMALS): string
    {
        return number_format($value, $decimals, '', '');
    }
}
