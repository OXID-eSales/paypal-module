<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Money;
use stdClass;

class PriceToMoney
{
    public const BRUTTO_MODE = 1;
    public const NETTO_MODE = 2;

    /**
     * @param Price | double $price
     * @param int $mode sets which price value to use with BRUTTO_MODE or NETTO_MODE constants.
     * If not set uses the mode set in the price object.
     * @param stdClass $currency currency object. If not set uses the active shop currency.
     *
     * @return Money
     */
    public static function convert($price, $currency = null, int $mode = 0): Money
    {
        if ($price instanceof Price) {
            if ($mode === self::BRUTTO_MODE) {
                $value = $price->getBruttoPrice();
            } elseif ($mode === self::NETTO_MODE) {
                $value = $price->getNettoPrice();
            } else {
                $value = $price->getPrice();
            }
        } else {
            $value = (double) $price;
        }

        if (!$currency) {
            $currency = Registry::getConfig()->getActShopCurrencyObject();
        }
        $value = Registry::getUtils()->fRound((string)$value, $currency);
        $value = number_format($value, (int) $currency->decimal, '.', '');

        $money = new Money();
        $money->currency_code = $currency->name;
        $money->value = $value;

        return $money;
    }
}
