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
     * return the Payment-Sourcename for uAPM-Payments
     *
     * @return string
     */
    public function getUAPMPaymentSource(): string
    {
        return PayPalDefinitions::getUAPMPaymentSource($this->getId());
    }
}
