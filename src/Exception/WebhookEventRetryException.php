<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Exception;

use Exception;

class WebhookEventRetryException extends Exception
{
    /**
     * When set, the webhook should be redelivered by PayPal (HTTP 503 + Retry-After) instead of
     * being acknowledged with a 200. Null means "give up / benign" — respond 200 so PayPal stops.
     */
    private ?int $retryAfter = null;

    /**
     * Signals a transient condition where PayPal should retry the delivery later: e.g. the shop
     * order is still inside the finalizeOrder() transaction and not yet visible on this connection,
     * or delivery is deliberately delayed until the frontend has persisted.
     */
    public static function retry(string $message, int $retryAfter): self
    {
        $exception = new self($message);
        $exception->retryAfter = max(0, $retryAfter);

        return $exception;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function isRetryable(): bool
    {
        return $this->retryAfter !== null;
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
