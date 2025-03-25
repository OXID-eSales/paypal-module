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
     * Check if payment method is PayPal payment
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

    /**
     * @inheritDoc
     *
     * @return string|bool
     */
    public function save()
    {
        // PayPalExpress could not be a default Payment
        if ($this->getId() === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID) {
            $this->oxpayments__oxchecked = new Field(0);
        }
        return parent::save();
    }
}
