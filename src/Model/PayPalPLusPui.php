<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

/**
 * Class paypPayPalPlusPuiData
 *
 * Data model for Pay upon Invoice. This model reflects the payment instructions given by PayPal
 * in case "Payment upon Invoice" was chosen by the user.
 */
class PayPalPLusPui extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'payppaypalpluspui'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Collection of required fields
     * These fields must be set on saving the object to the database.
     *
     * @var array
     */
    protected $_aRequiredFields = array(
        'paymentid'       => array('_validateNotEmptyString'),
        'referencenumber' => array('_validateNotEmptyString'),
        'bankname'        => array('_validateNotEmptyString'),
        'accountholder'   => array('_validateNotEmptyString'),
        'iban'            => array('_validateIBAN'),
        'bic'             => array('_validateBic'),
        'duedate'         => array('_validateFutureDate'),
        'total'           => array('_validateNotEmptyFloat'),
        'currency'        => array('_validateCurrency'),
        'puiobject'       => array('_validateNotEmptyString', '_validateValidJson'),
    );

    /**
     * Set the payment ID identifying this transaction.
     *
     * @param string $sValue
     */
    public function setPaymentId($sValue)
    {
        $this->payppaypalpluspui__oxpaymentid = new oxField($sValue);
    }

    /**
     * Set the PayPal Reference number.
     *
     * @param string $sValue
     */
    public function setReferenceNumber($sValue)
    {
        $this->payppaypalpluspui__oxreferencenumber = new oxField($sValue);
    }

    /**
     * Set the due date of the invoice
     *
     * @param string $sValue
     */
    public function setDueDate($sValue)
    {
        $this->payppaypalpluspui__oxduedate = new oxField($sValue);
    }

    /**
     * Set the total of the invoice.
     * This is an amount of money.
     *
     * @param float $fValue
     */
    public function setTotal($fValue)
    {
        $this->payppaypalpluspui__oxtotal = new oxField((float) $fValue);
    }

    /**
     * Set the currency of the invoice.
     *
     * @param string $sValue
     */
    public function setCurrency($sValue)
    {
        $this->payppaypalpluspui__oxcurrency = new oxField($sValue);
    }

    /**
     * Set the bank name, where the amount of money has to be transfered to.
     *
     * @param string $sValue
     */
    public function setBankName($sValue)
    {
        $this->payppaypalpluspui__oxbankname = new oxField($sValue);
    }

    /**
     * Set the holder of the bank account.
     *
     * @param string $sValue
     */
    public function setAccountHolder($sValue)
    {
        $this->payppaypalpluspui__oxaccountholder = new oxField($sValue);
    }

    /**
     * Set the IBAN of the bank account.
     *
     * @param string $sValue
     */
    public function setIban($sValue)
    {
        $this->payppaypalpluspui__oxiban = new oxField($sValue);
    }

    /**
     * Set the BIC of the bank.
     *
     * @param string $sValue
     */
    public function setBic($sValue)
    {
        $this->payppaypalpluspui__oxbic = new oxField($sValue);
    }

    /**
     * @param \PayPal\Api\PaymentInstruction $oValue
     */
    public function setPuiObject(\PayPal\Api\PaymentInstruction $oValue)
    {
        $this->payppaypalpluspui__oxpuiobject = new oxField($oValue->toJSON(), oxField::T_RAW);
    }

    /**
     * Get the payment ID identifying this transaction.
     */
    public function getPaymentId()
    {
        return $this->payppaypalpluspui__oxpaymentid->value;
    }

    /**
     * Get the PayPal Reference number.
     */
    public function getReferenceNumber()
    {
        return $this->payppaypalpluspui__oxreferencenumber->value;
    }

    /**
     * Get the due date of the invoice
     */
    public function getDueDate()
    {
        return $this->payppaypalpluspui__oxduedate->value;
    }

    /**
     * Get the total of the invoice.
     * This is an amount of money.
     */
    public function getTotal()
    {
        return $this->payppaypalpluspui__oxtotal->value;
    }

    /**
     * get the currency of the invoice.
     */
    public function getCurrency()
    {
        return $this->payppaypalpluspui__oxcurrency->value;
    }

    /**
     * get the bank name, where the amount of money has to be transfered to.
     */
    public function getBankName()
    {
        return $this->payppaypalpluspui__oxbankname->value;
    }

    /**
     * get the holder of the bank account.
     */
    public function getAccountHolder()
    {
        return $this->payppaypalpluspui__oxaccountholder->value;
    }

    /**
     * get the IBAN of the bank account.
     */
    public function getIban()
    {
        return $this->payppaypalpluspui__oxiban->value;
    }

    /**
     * get the BIC of the bank.
     */
    public function getBic()
    {
        return $this->payppaypalpluspui__oxbic->value;
    }

    /**
     */
    public function getPuiObject()
    {
        $sJson = $this->payppaypalpluspui__oxpuiobject->getRawValue();
        $oObject = new \PayPal\Api\PaymentInstruction();
        return $oObject->fromJson($sJson);
    }

    /**
     * Validates the data and calls parent save method
     *
     * Do validation in the save method rather then receiving the data from outside in order to absolutely ensure that
     * only a valid object state gets stored in the database
     *
     * @inheritdoc
     */
    public function save()
    {
        $this->_validateData();

        return $this->_paypPayPalPlusPuiData_save_parent();
    }

    /**
     * Getter for required fields
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->_aRequiredFields;
    }

    /**
     * Load entry by payment ID.
     *
     * @param string $sPaymentId
     *
     * @return bool
     */
    public function loadByPaymentId($sPaymentId)
    {
        return $this->_loadBy('OXPAYMENTID', $sPaymentId);
    }

    /**
     * Load entry by payment ID.
     *
     * @param string $sPaymentId
     *
     * @return bool
     */
    public function loadByReferenceNumber($sReferenceNumber)
    {
        return $this->_loadBy('OXREFERENCENUMBER', $sReferenceNumber);
    }

        /**
     * Load entry by a field name and value.
     * Used for loading by `OXORDERID`, `OXSALEID` and `OXPAYMENTID`.
     *
     * @param string $sFieldName
     * @param string $sFieldValue
     *
     * @return bool
     */
    protected function _loadBy($sFieldName, $sFieldValue)
    {
        if (!in_array($sFieldName, array('OXREFERENCENUMBER', 'OXPAYMENTID'))) {
            return false;
        }

        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `%s` = %s",
            $this->getCoreTableName(),
            $sFieldName,
            oxDb::getDb()->quote($sFieldValue)
        );
        $this->_isLoaded = $this->assignRecord($sSelect);

        return $this->_isLoaded;
    }

    /**
     * Validate required fields in the object
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateData()
    {
        $aValidationErrors = array();

        foreach ($this->getRequiredFields() as $sFieldName => $aValidators) {
            foreach ($aValidators as $sCallback) {
                try {
                    /**
                     * call_user_func_array Returns the return value of the callback, or FALSE on error, so we
                     * will not use the return value to know if a field is valid.
                     * Instead every callback will throw an Exception on vaildation error
                     */
                    $blValidatorCouldBeCalled = call_user_func_array(array($this, $sCallback), array($sFieldName));
                    if (false === $blValidatorCouldBeCalled || is_null($blValidatorCouldBeCalled)) {
                        $sMessage = 'Validator callback caused an error: ' . $sCallback;
                        $this->_throwValidatorCouldNotBeCalledException($sMessage);
                    }
                } catch (\InvalidArgumentException $oException) {
                    $aValidationErrors[] = $oException->getMessage();
                }
            }
        }

        if (!empty($aValidationErrors)) {
            $sMessage = "Received data did not validate:\n- " . implode("\n- ", $aValidationErrors);
            $this->_throwInvalidDataException($sMessage);
        }
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a string
     * - is not empty
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateNotEmptyString($sFieldName)
    {
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;

        if (!isset($this->$sPropertyName->value) || !is_string($this->$sPropertyName->value)
        ) {
            $sMessage = $sFieldName . " must be a string";
            $this->_throwEmptyStringException($sMessage);
        }
        if (empty($this->$sPropertyName->value)) {
            $sMessage = $sFieldName . " must not be an empty string";
            $this->_throwEmptyStringException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a string
     * - something like an IBAN
     *
     * This is a very simple validation, that just checks the IBAN country Coda and the corresponding length of the
     * string
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateIBAN($sFieldName)
    {
        $aIbanSpecs = array(
            'AA' => 16,
            'AL' => 28,
            'AD' => 24,
            'AT' => 20,
            'AX' => 18,
            'AZ' => 28,
            'BH' => 22,
            'BE' => 16,
            'BA' => 20,
            'BR' => 29,
            'BG' => 22,
            'CR' => 21,
            'HR' => 21,
            'CY' => 28,
            'CZ' => 24,
            'DK' => 18,
            'FO' => 18,
            'GL' => 18,
            'DO' => 28,
            'EE' => 20,
            'FI' => 18,
            'FR' => 27,
            'BL' => 27,
            'GF' => 27,
            'GP' => 27,
            'MF' => 27,
            'MQ' => 27,
            'RE' => 27,
            'PF' => 27,
            'TF' => 27,
            'YT' => 27,
            'NC' => 27,
            'PM' => 27,
            'WF' => 27,
            'GE' => 22,
            'DE' => 22,
            'GI' => 23,
            'GR' => 27,
            'GT' => 28,
            'HU' => 28,
            'IS' => 26,
            'IE' => 22,
            'IL' => 23,
            'IT' => 27,
            'JO' => 30,
            'KW' => 30,
            'LV' => 21,
            'LB' => 28,
            'LI' => 21,
            'LT' => 20,
            'LU' => 20,
            'MK' => 19,
            'MT' => 31,
            'MR' => 27,
            'MU' => 30,
            'MD' => 24,
            'MC' => 27,
            'ME' => 22,
            'NL' => 18,
            'NO' => 15,
            'PK' => 24,
            'PL' => 28,
            'PS' => 29,
            'PT' => 25,
            'QA' => 29,
            'RO' => 24,
            'SM' => 27,
            'SA' => 24,
            'RS' => 22,
            'SK' => 24,
            'SI' => 19,
            'ES' => 24,
            'SE' => 24,
            'CH' => 21,
            'TN' => 24,
            'TR' => 26,
            'AE' => 23,
            'GB' => 22,
            'VG' => 24,
        );
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;
        $sFieldValue = $this->$sPropertyName->value;

        if (!is_string($sFieldValue)) {
            $sMessage = $sFieldName . " must be a string";
            $this->_throwInvalidIbanException($sMessage);
        }

        if (empty($sFieldValue)) {
            $sMessage = $sFieldName . " must not be a empty";
            $this->_throwInvalidIbanException($sMessage);
        }

        $sIban = strtolower(str_replace(' ', '', $sFieldValue));
        $sIbanCountryCode = strtoupper(substr($sIban, 0, 2));
        $iIbanLength = (int) $aIbanSpecs[$sIbanCountryCode];

        if ($iIbanLength != strlen($sIban)) {
            $sMessage = $sFieldName . " string has not the right length " . $sIban;
            $this->_throwInvalidIbanException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a string
     * - something like a Bic
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateBic($sFieldName)
    {
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;
        $sFieldValue = $this->$sPropertyName->value;

        if (!is_string($sFieldValue)) {
            $sMessage = $sFieldName . " must be a string";
            $this->_throwInvalidBicException($sMessage);
        }

        if (empty($sFieldValue)) {
            $sMessage = $sFieldName . " must not be a empty";
            $this->_throwInvalidBicException($sMessage);
        }
        $sBic = strtolower(str_replace(' ', '', $sFieldValue));
        if (!preg_match('/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i', $sBic)) {
            $sMessage = $sFieldName . " has not the right format " . $sBic;
            $this->_throwInvalidBicException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a date
     * - not too long in the past
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateFutureDate($sFieldName)
    {
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;
        $sFieldValue = $this->$sPropertyName->value;
        $oDate = DateTime::createFromFormat('Y-m-d H:i:s', $sFieldValue);
        if (false === $oDate) {
            $sMessage = $sFieldName . ' is not a date in format YYYY-MM-DD ' . $sFieldValue;
            $this->_throwNoFutureDateException($sMessage);
        }

        $oSomeTimeAgo = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
        $oSomeTimeAgo->sub(new DateInterval('PT12H'));

        if ($oDate < $oSomeTimeAgo) {
            $sMessage = $sFieldName . ' date lies in the past ' . $sFieldValue;
            $this->_throwNoFutureDateException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a float
     * - is not empty
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateNotEmptyFloat($sFieldName)
    {
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;

        if (
            !isset($this->$sPropertyName->value) ||
            !is_float($this->$sPropertyName->value) ||
            0 == $this->$sPropertyName->value

        ) {
            $sMessage = $sFieldName . " must be a not empty float: " . $this->$sPropertyName->value;
            $this->_throwEmptyFloatException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - a valid ISO 4217 currency
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateCurrency($sFieldName)
    {
        $aValidIso4217Codes = array(
            'AED',
            'AFN',
            'ALL',
            'AMD',
            'ANG',
            'AOA',
            'ARS',
            'AUD',
            'AWG',
            'AZN',
            'BAM',
            'BBD',
            'BDT',
            'BGN',
            'BHD',
            'BIF',
            'BMD',
            'BND',
            'BOB',
            'BOV',
            'BRL',
            'BSD',
            'BTN',
            'BWP',
            'BYR',
            'BZD',
            'CAD',
            'CDF',
            'CHE',
            'CHF',
            'CHW',
            'CLF',
            'CLP',
            'CNY',
            'COP',
            'COU',
            'CRC',
            'CUC',
            'CUP',
            'CVE',
            'CZK',
            'DJF',
            'DKK',
            'DOP',
            'DZD',
            'EGP',
            'ERN',
            'ETB',
            'EUR',
            'FJD',
            'FKP',
            'GBP',
            'GEL',
            'GHS',
            'GIP',
            'GMD',
            'GNF',
            'GTQ',
            'GYD',
            'HKD',
            'HNL',
            'HRK',
            'HTG',
            'HUF',
            'IDR',
            'ILS',
            'INR',
            'IQD',
            'IRR',
            'ISK',
            'JMD',
            'JOD',
            'JPY',
            'KES',
            'KGS',
            'KHR',
            'KMF',
            'KPW',
            'KRW',
            'KWD',
            'KYD',
            'KZT',
            'LAK',
            'LBP',
            'LKR',
            'LRD',
            'LSL',
            'LYD',
            'MAD',
            'MDL',
            'MGA',
            'MKD',
            'MMK',
            'MNT',
            'MOP',
            'MRO',
            'MUR',
            'MVR',
            'MWK',
            'MXN',
            'MXV',
            'MYR',
            'MZN',
            'NAD',
            'NGN',
            'NIO',
            'NOK',
            'NPR',
            'NZD',
            'OMR',
            'PAB',
            'PEN',
            'PGK',
            'PHP',
            'PKR',
            'PLN',
            'PYG',
            'QAR',
            'RON',
            'RSD',
            'RUB',
            'RWF',
            'SAR',
            'SBD',
            'SCR',
            'SDG',
            'SSP',
            'SEK',
            'SGD',
            'SHP',
            'SLL',
            'SOS',
            'SRD',
            'STD',
            'SVC',
            'SYP',
            'SZL',
            'THB',
            'TJS',
            'TMT',
            'TND',
            'TOP',
            'TRY',
            '(20',
            'TTD',
            'TWD',
            'TZS',
            'UAH',
            'UGX',
            'USD',
            'UYI',
            'UYU',
            'UZS',
            'VEF',
            'VND',
            'VUV',
            'WST',
            'XAF',
            'XCD',
            'XOF',
            'XPF',
            'YER',
            'ZAR',
            'ZMW',
            'ZWL');
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;
        $sFieldValue = $this->$sPropertyName->value;
        if (!in_array($sFieldValue, $aValidIso4217Codes)) {
            $sMessage = $sFieldName . " is not a valid ISO 4217 currency";
            $this->_throwInvalidCurrencyException($sMessage);
        }

        return true;
    }

    /**
     * Validator callback.
     * Assures that the field value to be written in database is:
     * - valid JSON
     *
     * @param $sFieldName
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function _validateValidJson($sFieldName)
    {
        $sPropertyName = 'payppaypalpluspui__ox' . $sFieldName;

        $aDecodedJson = json_decode($this->$sPropertyName->getRawValue(), true);
        if (!is_array($aDecodedJson) || empty($aDecodedJson)) {
            $sMessage = $sFieldName . " must be valid, not empty JSON";
            $this->_throwNotValidJsonException($sMessage);
        }

        return true;
    }

    /**
     * Call parent save method.
     * Helper for testing.
     *
     * @codeCoverageIgnore
     *
     * @return bool|string
     */
    protected function _paypPayPalPlusPuiData_save_parent()
    {
        return parent::save();
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwInvalidArgumentException($sMessage)
    {
        throw new \InvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwValidatorCouldNotBeCalledException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwEmptyStringException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwInvalidIbanException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwInvalidBicException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwNoFutureDateException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwEmptyFloatException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwInvalidCurrencyException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwNotValidJsonException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }

    /**
     * Custom Exception
     *
     * @param $sMessage
     *
     * @throws \InvalidArgumentException
     */
    protected function _throwInvalidDataException($sMessage)
    {
        $this->_throwInvalidArgumentException($sMessage);
    }
}