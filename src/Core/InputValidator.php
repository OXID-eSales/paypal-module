<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\InputException;
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
            $exception = oxNew(InputException::class);
            $exception->setMessage(
                Registry::getLang()->translateString(
                    'OSC_PAYPAL_PAY_EXPRESS_ERROR_DELCOUNTRY'
                )
            );
            $this->addValidationError("oxuser__oxcountryid", $exception);
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
        $allValidationErrors = $this->getFieldValidationErrors();
        if (count($allValidationErrors) && PayPalSession::getCheckoutOrderId()) {
            $this->_aInputValidationErrors = [];
            $validationErrorKey = key($allValidationErrors);
            $exception = oxNew(
                InputException::class,
                Registry::getLang()->translateString('OSC_PAYPAL_PAY_EXPRESS_ERROR_INPUTVALIDATION')
            );
            $this->addValidationError($validationErrorKey, $exception);
        }
    }
}
