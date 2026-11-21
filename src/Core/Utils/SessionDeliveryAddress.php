<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Order;

/**
 * The delivery address selected for the current checkout ("deladrid"), resolved the same way
 * the shop resolves it when it stores the order.
 *
 * Reading "deladrid" and loading the address directly is not enough: since OXID 7.6,
 * Order::getDelAddressInfo() only accepts an address that belongs to the active user, and
 * otherwise stores the order with the billing address as delivery address. Anything sent to
 * PayPal (shipping address, delivery country) has to follow the same rule, or PayPal gets an
 * address that differs from the one in the shop order. Delegating to the core keeps the
 * behaviour of every shop version (7.3 - 7.5 do not check the owner).
 */
class SessionDeliveryAddress
{
    public static function get(): ?Address
    {
        $order = oxNew(Order::class);
        $address = $order->getDelAddressInfo();

        // before OXID 7.6 the core returns an empty address object if the id cannot be loaded
        if (!$address instanceof Address || !$address->isLoaded()) {
            return null;
        }

        return $address;
    }
}
