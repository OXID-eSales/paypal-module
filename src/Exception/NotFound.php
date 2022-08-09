<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use Exception;

class NotFound extends Exception
{
    protected const NOT_FOUND_MESSAGE = 'Queried data was not found';
    protected const ORDER_NOT_FOUND_BY_PAYPAL_MESSAGE = 'Shop order not found by PayPal order id';
    protected const ORDER_NOT_FOUND_BY_PAYPAL_TRANSACTION_MESSAGE = 'Shop order not found by PayPal transaction id';
    protected const ORDER_NOT_FOUND_MESSAGE = 'Shop order not found';

    public static function notFound(): self
    {
        return new self(self::NOT_FOUND_MESSAGE);
    }

    public static function orderNotFoundByPayPalOrderId(): self
    {
        return new self(self::ORDER_NOT_FOUND_BY_PAYPAL_MESSAGE);
    }

    public static function orderNotFoundByPayPalTransactionId(): self
    {
        return new self(self::ORDER_NOT_FOUND_BY_PAYPAL_TRANSACTION_MESSAGE);
    }


    public static function orderNotFound(): self
    {
        return new self(self::ORDER_NOT_FOUND_MESSAGE);
    }
}
