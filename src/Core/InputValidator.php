<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;

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
        $fieldValidationErrors = $this->getFieldValidationErrors();
        if (isset($fieldValidationErrors['oxuser__oxcountryid']) && PayPalSession::getCheckoutOrderId()) {
            $this->_aInputValidationErrors = [];
            $exception = oxNew(UserException::class);
            $exception->setMessage(
                Registry::getLang()->translateString(
                    'OSC_PAYPAL_PAY_EXPRESS_ERROR_DELCOUNTRY'
                )
            );
            $this->_addValidationError("oxuser__oxcountryid", $exception);
        }
    }
}
