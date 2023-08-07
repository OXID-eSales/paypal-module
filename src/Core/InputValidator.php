<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\User;
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

    /**
     * Checking if all required fields were filled. In case of error
     * exception is thrown
     *
     * @param User  $user            Active user.
     * @param array $billingAddress  Billing address.
     * @param array $deliveryAddress Delivery address.
     */
    public function checkRequiredFields($user, $billingAddress, $deliveryAddress)
    {
        parent::checkRequiredFields($user, $billingAddress, $deliveryAddress);
        $firstValidationError = $this->getFirstValidationError();
        if ($firstValidationError && PayPalSession::getCheckoutOrderId()) {
            $this->_aInputValidationErrors = [];
            $firstValidationErrorKey = key($firstValidationError);
            $exception = oxNew(UserException::class);
            $exception->setMessage(
                Registry::getLang()->translateString(
                    'OSC_PAYPAL_PAY_EXPRESS_ERROR_INPUTVALIDATION'
                )
            );
            $this->_addValidationError($firstValidationErrorKey, $exception);
        }
    }
}
