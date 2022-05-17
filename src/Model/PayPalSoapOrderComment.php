<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

/**
 * PayPal order payment comment class
 */
class PayPalSoapOrderComment extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'oepaypal_orderpaymentcomments'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Returns comment id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->getCommentId();
    }

    /**
     * Set PayPal comment Id
     *
     * @return string
     */
    public function getCommentId(): string
    {
        return (string) $this->getFieldData('oepaypal_commentid');
    }

    /**
     * Set PayPal order payment Id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return (string) $this->getFieldData('oepaypal_paymentid');
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate(): string
    {
        return (string) $this->getFieldData('oepaypal_date');
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->getFieldData('oepaypal_comment');
    }
}
