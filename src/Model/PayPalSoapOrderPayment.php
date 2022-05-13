<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrderCommentList;

/**
 * PayPal order payment list class
 */
class PayPalSoapOrderPayment extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'oepaypal_orderpayments'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Set PayPal comment Id.
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->getPaymentId();
    }

    /**
     * Payment comments
     *
     * @var array
     */
    protected $commentList = null;

    /**
     * Set PayPal comment Id.
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return (string) $this->getFieldData('oepaypal_paymentid');
    }

    /**
     * Returns PayPal payment action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return (string) $this->getFieldData('oepaypal_action');
    }

    /**
     * Returns PayPal payment OrderId.
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return (string) $this->getFieldData('oepaypal_orderid');
    }

    /**
     * Returns PayPal payment amount.
     *
     * @return float
     */
    public function getAmount(): float
    {
        return (float) $this->getFieldData('oepaypal_amount');
    }

    /**
     * Get PayPal refunded amount
     *
     * @return float
     */
    public function getRefundedAmount(): float
    {
        return (float) $this->getFieldData('oepaypal_refundedamount');
    }

    /**
     * Returns not yet captured (remaining) order sum
     *
     * @return string
     */
    public function getRemainingRefundAmount(): string
    {
        $amount = $this->getAmount() - $this->getRefundedAmount();

        return sprintf("%.2f", round($amount, 2));
    }

    /**
     * Returns PayPal payment status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return (string) $this->getFieldData('oepaypal_status');
    }

    /**
     * Returns PayPal payment date.
     *
     * @return string
     */
    public function getDate(): string
    {
        return (string) $this->getFieldData('oepaypal_date');
    }

    /**
     * Sets PayPal payment currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string) $this->getFieldData('oepaypal_currency');
    }

    /**
     *  Returns PayPal payment transaction id
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return (string) $this->getFieldData('oepaypal_transactionid');
    }

    /**
     *  Returns PayPal payment correlation id
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        return (string) $this->getFieldData('oepaypal_correlationid');
    }

    /**
     * Get comments
     *
     * @return array
     */
    public function getCommentList(): ?PayPalSoapOrderCommentList
    {
        if (is_null($this->commentList)) {
            $comments = oxNew(PayPalSoapOrderCommentList::class);
            $comments->load($this->getPaymentId());
            $this->commentList = $comments;
        }
        return $this->commentList;
    }
}
