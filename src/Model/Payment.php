<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

class Payment extends Payment_parent
{
    public Field $oxpayments__oxactive;

    /**
     * Checks if the payment method is an uAPM payment method
     *
     * @return bool
     */
    public function isUAPMPayment(): bool
    {
        return PayPalDefinitions::isUAPMPayment($this->getId());
    }
}
