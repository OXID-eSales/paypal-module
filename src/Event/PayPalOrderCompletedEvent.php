<?php

namespace OxidSolutionCatalysts\PayPal\Event;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use Symfony\Component\EventDispatcher\Event;

class PayPalOrderCompletedEvent extends Event
{
    public const NAME = 'osc.paypal.order.completed';

    private $order;
    private $basket;
    private $user;
    private $shopOrderId;
    private $payPalOrderId;
    private $paymentsId;
    private $transactionId;

    public function __construct(
        Order $order,
        ?Basket $basket,
        ?User $user,
        string $shopOrderId,
        string $payPalOrderId,
        string $paymentsId,
        string $transactionId
    ) {
        $this->order = $order;
        $this->basket = $basket;
        $this->user = $user;
        $this->shopOrderId = $shopOrderId;
        $this->payPalOrderId = $payPalOrderId;
        $this->paymentsId = $paymentsId;
        $this->transactionId = $transactionId;
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
}
