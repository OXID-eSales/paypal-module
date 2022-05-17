<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrderPaymentList;

class PayPalSoapOrder extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'oepaypal_order'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /** Completion status */
    protected const PAYPAL_ORDER_STATE_COMPLETED = 'completed';

    /**
     * List of order payments.
     *
     * @var PayPalSoapOrderPaymentList
     */
    protected $paymentList = null;

    /**
     * Load entry by order ID.
     *
     * @param string $sOrderId
     *
     * @return bool
     */
    public function loadByOrderId($sOrderId)
    {
        return $this->_loadBy('OEPAYPAL_ORDERID', $sOrderId);
    }

    /**
     * Returns order id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getOrderId();
    }

    /**
     * Set PayPal order Id.
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return (string) $this->getFieldData('oepaypal_orderid');
    }

    /**
     * Get PayPal captured amount.
     *
     * @return float
     */
    public function getCapturedAmount(): float
    {
        return (float) $this->getFieldData('oepaypal_capturedamount');
    }

    /**
     * Get PayPal refunded amount.
     *
     * @return float
     */
    public function getRefundedAmount(): float
    {
        return (float) $this->getFieldData('oepaypal_refundedamount');
    }

    /**
     * Returns not yet captured (remaining) order sum.
     *
     * @return float
     */
    public function getRemainingRefundAmount(): float
    {
        return round($this->getCapturedAmount() - $this->getRefundedAmount(), 2);
    }

    /**
     * Get PayPal refunded amount.
     *
     * @return float
     */
    public function getVoidedAmount(): float
    {
        return (float) $this->getFieldData('oepaypal_voidedamount');
    }

    /**
     * Get transaction mode.
     *
     * @return string
     */
    public function getTransactionMode(): string
    {
        return (string) $this->getFieldData('oepaypal_transactionmode');
    }

    /**
     * Get payment status.
     *
     * @return string
     */
    public function getPaymentStatus(): string
    {
        $state = $this->getFieldData('oepaypal_paymentstatus');
        if (empty($state)) {
            $state = self::PAYPAL_ORDER_STATE_COMPLETED;
        }

        return (string) $state;
    }

    /**
     * Returns total order sum.
     *
     * @return float
     */
    public function getTotalOrderSum(): float
    {
        return (float) $this->getFieldData('oepaypal_totalordersum');
    }

    /**
     * Returns not yet captured (remaining) order sum.
     *
     * @return float
     */
    public function getRemainingOrderSum(): float
    {
        return $this->getTotalOrderSum() - $this->getCapturedAmount();
    }

    /**
     * Returns order currency.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string) $this->getFieldData('oepaypal_currency');
    }

    /**
     * Return order payment list.
     *
     * @return PayPalSoapOrderPaymentList
     */
    public function getPaymentList(): ?PayPalSoapOrderPaymentList
    {
        if (is_null($this->paymentList)) {
            $paymentList = oxNew(PayPalSoapOrderPaymentList::class);
            $paymentList->load($this->getOrderId());
            $this->paymentList = $paymentList;
        }
        return $this->paymentList;
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
        if (!in_array($sFieldName, ['OEPAYPAL_ORDERID'])) {
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
}
