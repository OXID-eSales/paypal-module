<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Article as EshopModelArticle;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Model\PayPalPLusRefundList;

class PayPalPlusOrder extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'payppaypalpluspayment'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * OXID eShop order object associated with the payment.
     *
     * @var null|oxOrder
     */
    protected $_oOrder = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * List of refunds associated with the payment.
     *
     * @var null|paypPayPalPlusRefundDataList
     */
    protected $_oRefundList = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * A sum of refunded amounts for the payment.
     *
     * @var null|float
     */
    protected $_dTotalAmountRefunded = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Wrapper property for PayPal completed status
     *
     * @var array
     */
    protected $_sPayPalStatusCompleted = 'completed';

    /**
     * Construct initialize class
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Get OXID eShop oxOrder model primary key value.
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->getFieldData('oxorderid');
    }

    /**
     * Get PayPal Plus Payment model sale ID.
     *
     * @return string
     */
    public function getSaleId(): string
    {
        return $this->getFieldData('oxsaleid');
    }

    /**
     * Get PayPal Plus payment (transaction) ID.
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->getFieldData('oxpaymentid');
    }

    /**
     * Get PayPal Plus Payment sale status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getFieldData('oxstatus');
    }

    /**
     * Get PayPal Plus Payment creation date and time.
     *
     * @return string
     */
    public function getDateCreated(): string
    {
        return $this->getFieldData('oxdatecreated');
    }

    /**
     * Get PayPal Plus Payment grand total amount.
     *
     * @return float
     */
    public function getTotal(): float
    {
        return (float)$this->getFieldData('oxtotal');
    }

    /**
     * Get PayPal Plus Payment currency code related to the total amount.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->getFieldData('oxcurrency');
    }

    /**
     * Get PayPal Plus Payment object un-serialized.
     *
     * @return bool|\PayPal\Api\Payment
     */
    public function getPaymentObject()
    {
        $oPayment = null;

        if ($this->payppaypalpluspayment__oxpaymentobject instanceof oxField) {
            try {
                $oSdk = $this->getShop()->getFromRegistry('paypPayPalPlusSdk');
                $oPayment = $oSdk->newPayment();
                $oPayment->fromJson($this->payppaypalpluspayment__oxpaymentobject->getRawValue());
            } catch (Exception $e) {
            }
        }

        return $oPayment;
    }

    /**
     * Check if the payment can be refunded.
     *
     * @return bool
     */
    public function isRefundable()
    {
        return ($this->getStatus() === $this->_sPayPalStatusCompleted);
    }

    /**
     * Load entry by order ID.
     *
     * @param string $sOrderId
     *
     * @return bool
     */
    public function loadByOrderId($sOrderId)
    {
        return $this->_loadBy('OXORDERID', $sOrderId);
    }

    /**
     * Load entry by sale ID.
     *
     * @param string $sSaleId
     *
     * @return bool
     */
    public function loadBySaleId($sSaleId)
    {
        return $this->_loadBy('OXSALEID', $sSaleId);
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
     * Get OXID eShop order associated with the PayPal Plus Payment.
     * Throw an exception if the order is not loaded (each payment must be based on an order).
     *
     * @return null|oxOrder
     * @throws oxException
     */
    public function getOrder()
    {
        if (is_null($this->_oOrder)) {

            /** @var oxOrder $oOrder */
            $oOrder = oxNew(Order::class);

            if ($oOrder->load($this->getOrderId())) {
                $this->_oOrder = $oOrder;
            } else {
                $this->_throwCouldNotLoadOrderError();
            }
        }

        return $this->_oOrder;
    }

    /**
     * Get a list of related refunds by sale ID.
     *
     * @return null|paypPayPalPlusRefundDataList
     */
    public function getRefundsList()
    {
        if (is_null($this->_oRefundList)) {

            /** @var PayPalPLusRefundList $oRefundList */
            $oRefundList = oxNew(PayPalPLusRefundList::class);
            $oRefundList->loadRefundsBySaleId($this->getSaleId());

            if ($oRefundList->count() > 0) {
                $this->_oRefundList = $oRefundList;
            }
        }

        return $this->_oRefundList;
    }

    /**
     * Calculate a total amount already refunded for the payment.
     *
     * @return float
     */
    public function getTotalAmountRefunded(): float
    {
        if (is_null($this->_dTotalAmountRefunded)) {

            /** @var PayPalPLusRefundList $oRefundList */
            $oRefundList = oxNew(PayPalPLusRefundList::class);
            $this->_dTotalAmountRefunded = (double) $oRefundList->getRefundedSumBySaleId($this->getSaleId());
        }

        return (float)$this->_dTotalAmountRefunded;
    }

    public function getPaymentInstructions()
    {
        $oPaymentInstructions = null;
        $oPayPalPlusPuiData = oxNew('paypPayPalPlusPuiData');
        if ($oPayPalPlusPuiData->loadByPaymentId($this->getPaymentId())) {
            $oPaymentInstructions = $oPayPalPlusPuiData;
        }

        return $oPaymentInstructions;
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
        $db = DatabaseProvider::getDb();
        if (!in_array($sFieldName, array('OXORDERID', 'OXSALEID', 'OXPAYMENTID'))) {
            return false;
        }

        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `%s` = %s",
            $this->getCoreTableName(),
            $sFieldName,
            $db->quote($sFieldValue)
        );
        $this->_isLoaded = $this->assignRecord($sSelect);

        return $this->_isLoaded;
    }

    /**
     * Throw an exception with "order not loaded" message.
     *
     * @throws oxException
     */
    protected function _throwCouldNotLoadOrderError()
    {
        /** @var paypPayPalPlusNoOrderException $oEx */
        $oEx = $this->getShop()->getNew('paypPayPalPlusNoOrderException');
        $oEx->setMessage($this->getShop()->translate('OSC_PAYPALPLUS_ERROR_NO_ORDER'));

        throw $oEx;
    }
}
