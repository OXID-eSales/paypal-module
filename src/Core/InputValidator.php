<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

/**
 * @mixin \OxidEsales\Eshop\Core\ViewConfig
 */
class InputValidator extends InputValidator_parent
{
    /**
     * @InheritDoc
     */
    public function checkCountries($user, $invAddress, $deliveryAddress)
    {
        parent::checkCountries($user, $invAddress, $deliveryAddress);

        if ($this->getFirstValidationError() && PayPalSession::getCheckoutOrderId()) {
            $this->_aInputValidationErrors = [];
            $exception = oxNew(\OxidEsales\Eshop\Core\Exception\UserException::class);
            $exception->setMessage(\OxidEsales\Eshop\Core\Registry::getLang()->translateString('OSC_PAYPAL_PAY_EXPRESS_ERROR_DELCOUNTRY'));
            $this->_addValidationError("oxuser__oxcountryid", $exception);
        }
    }
}

