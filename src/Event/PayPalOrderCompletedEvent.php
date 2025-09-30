<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Event;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use Symfony\Component\EventDispatcher\GenericEvent;

class PayPalOrderCompletedEvent extends GenericEvent
{
    public const NAME = 'osc.paypal.order.completed';

    private $order;
    private $basket;
    private $user;
    private $shopOrderId;
    private $payPalOrderId;
    private $paymentsId;
    private $transactionId;
    private $payPalCustomerId; // optional vaulting customer id

    public function __construct(
        Order $order,
        ?Basket $basket,
        ?User $user,
        string $shopOrderId,
        string $payPalOrderId,
        string $paymentsId,
        string $transactionId,
        ?string $payPalCustomerId = null
    ) {
        $this->order = $order;
        $this->basket = $basket;
        $this->user = $user;
        $this->shopOrderId = $shopOrderId;
        $this->payPalOrderId = $payPalOrderId;
        $this->paymentsId = $paymentsId;
        $this->transactionId = $transactionId;
        $this->payPalCustomerId = $payPalCustomerId;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getShopOrderId(): string
    {
        return $this->shopOrderId;
    }

    public function getPayPalOrderId(): string
    {
        return $this->payPalOrderId;
    }

    public function getPaymentsId(): string
    {
        return $this->paymentsId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getPayPalCustomerId(): ?string
    {
        return $this->payPalCustomerId;
    }
}
