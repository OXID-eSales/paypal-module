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
            $this->_addValidationError("oxuser__oxcountryid", $exception);
        }
    }

    /**
     * Checking if all required fields were filled. In case of error
     * exception is thrown
     *
     * @param User $user             Active user.
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
            $exception = oxNew(InputException::class);
            $exception->setMessage(
                Registry::getLang()->translateString(
                    'OSC_PAYPAL_PAY_EXPRESS_ERROR_INPUTVALIDATION'
                )
            );
            $this->addValidationError($validationErrorKey, $exception);
        }
    }

    private function checkRequiredFields_ParentPath($user, $billingAddress, $deliveryAddress)
    {
        /** @var \OxidEsales\Eshop\Application\Model\RequiredAddressFields $requiredAddressFields */
        $requiredAddressFields = oxNew(\OxidEsales\Eshop\Application\Model\RequiredAddressFields::class);

        /** @var \OxidEsales\Eshop\Application\Model\RequiredFieldsValidator $fieldsValidator */
        $fieldsValidator = oxNew(\OxidEsales\Eshop\Application\Model\RequiredFieldsValidator::class);

        /// THE NEXT STRING IS REDUNDAND IN THE CORE CLASS
        //    $user = oxNew(User::class);
        $billingAddress = $this->_setFields($user, $billingAddress);
        $fieldsValidator->setRequiredFields($requiredAddressFields->getBillingFields());
        $fieldsValidator->validateFields($billingAddress);
        $invalidFields = $fieldsValidator->getInvalidFields();

        if (!empty($deliveryAddress)) {
            /** @var \OxidEsales\Eshop\Application\Model\Address $deliveryAddress */
            $deliveryAddress = $this->_setFields(oxNew(\OxidEsales\Eshop\Application\Model\Address::class), $deliveryAddress);
            $fieldsValidator->setRequiredFields($requiredAddressFields->getDeliveryFields());
            $fieldsValidator->validateFields($deliveryAddress);
            $invalidFields = array_merge($invalidFields, $fieldsValidator->getInvalidFields());
        }

        foreach ($invalidFields as $sField) {
            $exception = oxNew(\OxidEsales\Eshop\Core\Exception\InputException::class);
            $exception->setMessage(\OxidEsales\Eshop\Core\Registry::getLang()->translateString('ERROR_MESSAGE_INPUT_NOTALLFIELDS'));

            $this->_addValidationError($sField, $exception);
        }
    }
}
