<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidSolutionCatalysts\PayPal\Traits\DataGetter;

/**
 * PayPal order payment comment class
 */
class PayPalSoapOrderComment extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    use DataGetter;

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
        return $this->getPayPalStringData('oepaypal_commentid');
    }

    /**
     * Set PayPal order payment Id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->getPayPalStringData('oepaypal_paymentid');
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate(): string
    {
        return $this->getPayPalStringData('oepaypal_date');
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->getPayPalStringData('oepaypal_comment');
    }
}
