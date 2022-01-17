<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

class Currency
{
    /**
     * ISO-4217 currency codes that PayPal supports
     */
    private const CODES = [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'INR', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD',
        'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD'
    ];


    /**
     * Currency codes that PayPal does not support fractions for
     */
    private const NON_DECIMAL = [
        'JPY', 'HUF', 'TWD'
    ];

    /**
     * Get supported currency codes
     *
     * @return string[]
     */
    public static function getCurrencyCodes(): array
    {
        return self::CODES;
    }

    /**
     * Returns the amount value as a string in the lowest denomination of the currency
     * In case no currency is provided it formats the amount as a currency with 2 decimal positions
     *
     * @param $value
     * @param $currency
     *
     * @return string
     */
    public static function formatAmountInLowestDenominator(float $value, string $currency = null): string
    {
        $decimals = in_array($currency, self::NON_DECIMAL) ? 0 : 2;

        return number_format($value, $decimals, '', '');
    }
}
