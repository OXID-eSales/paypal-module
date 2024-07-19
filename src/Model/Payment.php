<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

class Payment extends Payment_parent
{
    /**
     * Checks if the payment method is an uAPM payment method
     *
     * @return bool
     */
    public function isUAPMPayment(): bool
    {
        return PayPalDefinitions::isUAPMPayment($this->getId());
    }

    /**
     * Check if payment method is paypal payment
     *
     * @return bool
     */
    public function isPayPalPayment(): bool
    {
        return PayPalDefinitions::isPayPalPayment($this->getId());
    }

    /**
     * Check if payment method is deprecated
     *
     * @return bool
     */
    public function isDeprecatedPayment(): bool
    {
        return PayPalDefinitions::isDeprecatedPayment($this->getId());
    }
}
