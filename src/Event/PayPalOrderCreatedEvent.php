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
use OxidSolutionCatalysts\PayPal\Event\PayPalOrderCompletedEvent;
use Symfony\Component\EventDispatcher\GenericEvent;

class PayPalOrderCreatedEvent extends PayPalOrderCompletedEvent
{
    public const NAME = 'osc.paypal.order.created';
}
