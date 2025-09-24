<?php

namespace OxidSolutionCatalysts\PayPal\Event;

use OxidEsales\Eshop\Application\Model\User;
use Symfony\Component\EventDispatcher\Event;

class PayPalVaultingSucceededEvent extends Event
{
    public const NAME = 'osc.paypal.vaulting.succeeded';

    /** @var User|null */
    private $user;

    /** @var string */
    private $payPalCustomerId;

    public function __construct(?User $user, string $payPalCustomerId)
    {
        $this->user = $user;
        $this->payPalCustomerId = $payPalCustomerId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getPayPalCustomerId(): string
    {
        return $this->payPalCustomerId;
    }
}
