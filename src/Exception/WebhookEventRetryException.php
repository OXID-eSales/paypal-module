<?php declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Exception;

use Exception;

class WebhookEventRetryException extends Exception
{
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