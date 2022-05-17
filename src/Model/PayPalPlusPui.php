<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class paypPayPalPlusPuiData
 *
 * Data model for Pay upon Invoice. This model reflects the payment instructions given by PayPal
 * in case "Payment upon Invoice" was chosen by the user.
 */
class PayPalPlusPui extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'payppaypalpluspui'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Get the payment ID identifying this transaction.
     */
    public function getPaymentId(): string
    {
        return (string)$this->getFieldData('oxpaymentid');
    }

    /**
     * Get the PayPal Reference number.
     */
    public function getReferenceNumber(): string
    {
        return (string)$this->getFieldData('oxreferencenumber');
    }

    /**
     * Get the due date of the invoice
     */
    public function getDueDate(): string
    {
        return (string)$this->getFieldData('oxduedate');
    }

    /**
     * Get the total of the invoice.
     * This is an amount of money.
     */
    public function getTotal(): float
    {
        return (float)$this->getFieldData('oxtotal');
    }

    /**
     * get the currency of the invoice.
     */
    public function getCurrency(): string
    {
        return (string)$this->getFieldData('oxcurrency');
    }

    /**
     * get the bank name, where the amount of money has to be transfered to.
     */
    public function getBankName(): string
    {
        return (string)$this->getFieldData('oxbankname');
    }

    /**
     * get the holder of the bank account.
     */
    public function getAccountHolder(): string
    {
        return (string)$this->getFieldData('oxaccountholder');
    }

    /**
     * get the IBAN of the bank account.
     */
    public function getIban(): string
    {
        return (string)$this->getFieldData('oxiban');
    }

    /**
     * get the BIC of the bank.
     */
    public function getBic(): string
    {
        return (string)$this->getFieldData('oxbic');
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
        if (!in_array($sFieldName, ['OXREFERENCENUMBER', 'OXPAYMENTID'])) {
            return false;
        }

        $db = DatabaseProvider::getDb();

        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `%s` = %s",
            $this->getCoreTableName(),
            $sFieldName,
            $db->quote($sFieldValue)
        );
        $this->_isLoaded = $this->assignRecord($sSelect);

        return $this->_isLoaded;
    }
}
