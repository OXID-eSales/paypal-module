<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Event;

use OxidEsales\Eshop\Application\Model\User;
use Symfony\Component\EventDispatcher\GenericEvent;

class PayPalVaultingSucceededEvent extends GenericEvent
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
