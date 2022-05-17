<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Article as EshopModelArticle;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Model\PayPalPlusRefundList;

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
     * @var null|Order
     */
    protected $order = null;

    /**
     * List of refunds associated with the payment.
     *
     * @var null|PayPalPlusRefundList
     */
    protected $refundList = null;

    /**
     * A sum of refunded amounts for the payment.
     *
     * @var null|float
     */
    protected $totalAmountRefunded = null;

    /**
     * Wrapper property for PayPal completed status
     *
     * @var string
     */
    protected $payPalStatusCompleted = 'completed';

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
     * Check if the payment can be refunded.
     *
     * @return bool
     */
    public function isRefundable()
    {
        return ($this->getStatus() === $this->payPalStatusCompleted);
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
     * @return null|Order
     * @throws StandardException
     */
    public function getOrder(): ?Order
    {
        if (is_null($this->order)) {

            /** @var Order $oOrder */
            $oOrder = oxNew(Order::class);

            if ($oOrder->load($this->getOrderId())) {
                $this->order = $oOrder;
            } else {
                $this->_throwCouldNotLoadOrderError();
            }
        }

        return $this->order;
    }

    /**
     * Get a list of related refunds by sale ID.
     *
     * @return null|PayPalPlusRefundList
     */
    public function getRefundsList(): ?PayPalPlusRefundList
    {
        if (is_null($this->refundList)) {

            /** @var PayPalPlusRefundList $oRefundList */
            $oRefundList = oxNew(PayPalPlusRefundList::class);
            $oRefundList->loadRefundsBySaleId($this->getSaleId());

            if ($oRefundList->count() > 0) {
                $this->refundList = $oRefundList;
            }
        }

        return $this->refundList;
    }

    /**
     * Calculate a total amount already refunded for the payment.
     *
     * @return float
     */
    public function getTotalAmountRefunded(): float
    {
        if (is_null($this->totalAmountRefunded)) {

            /** @var PayPalPlusRefundList $oRefundList */
            $oRefundList = oxNew(PayPalPlusRefundList::class);
            $this->totalAmountRefunded = $oRefundList->getRefundedSumBySaleId($this->getSaleId());
        }

        return (float)$this->totalAmountRefunded;
    }

    public function getPaymentInstructions()
    {
        $oPaymentInstructions = null;
        $oPayPalPlusPuiData = oxNew(PayPalPlusPui::class);
        if ($oPayPalPlusPuiData->loadByPaymentId($this->getPaymentId())) {
            $oPaymentInstructions = $oPayPalPlusPuiData;
        }

        return $oPaymentInstructions;
    }

    /**
     * check if payPalPlus Table exists
     *
     * @return bool
     */
    public function tableExists(): bool
    {
        $config = oxNew(Config::class);
        return $config->tableExists($this->getCoreTableName());
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
        if (!in_array($sFieldName, ['OXORDERID', 'OXSALEID', 'OXPAYMENTID'])) {
            return false;
        }

        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `%s` = %s",
            $this->getCoreTableName(),
            $sFieldName,
            $db->quote($sFieldValue)
        );
        return $this->assignRecord($sSelect);
    }

    /**
     * Throw an exception with "order not loaded" message.
     *
     * @throws StandardException
     */
    protected function _throwCouldNotLoadOrderError()
    {
        throw oxNew(StandardException::class, 'OSC_PAYPALPLUS_ERROR_NO_ORDER');
    }
}
