<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use Exception;

class WebhookEventException extends Exception
{
    public static function mandatoryDataNotFound(): self
    {
        return new self('Required data not found in request');
    }

    public static function byOrderId(string $orderOxId): self
    {
        return new self(sprintf("Order with oxorder.oxid '%s' not found", $orderOxId));
    }

    public static function byPayPalOrderId(string $payPalOrderId): self
    {
        return new self(sprintf("Shop Order for PayPal order '%s' not found", $payPalOrderId));
    }

    public static function byPayPalTransactionId(string $payPalTransactionId): self
    {
        return new self(sprintf("Shop Order for PayPal transaction '%s' not found", $payPalTransactionId));
    }
}
