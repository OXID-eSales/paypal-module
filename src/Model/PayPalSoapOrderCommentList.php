<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

/**
 * PayPal order payment comment list class
 */
class PayPalSoapOrderCommentList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName // phpcs:ignore PSR2.Classes.PropertyDeclaration
        = 'OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrderComment';

    /**
     * Selects and loads order payment history.
     *
     * @param string $paymentId Order id.
     */
    public function load($paymentId): void
    {
        $oBaseObject = $this->getBaseObject();
        $sPaymentTable = $oBaseObject->getViewName();

        // we could not use the simple $oBaseObject->getSelectFields(), because the table has no necessary oxid
        $sSelect = "select
            $sPaymentTable.`oepaypal_commentid`,
            $sPaymentTable.`oepaypal_paymentid`,
            $sPaymentTable.`oepaypal_date`,
            $sPaymentTable.`oepaypal_comment`
            from $sPaymentTable
            where $sPaymentTable.oepaypal_paymentid = " .
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($paymentId);

        $this->selectString($sSelect);
    }
}
