<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

/**
 * PayPal order payment list class
 */
class PayPalSoapOrderPaymentList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName // phpcs:ignore PSR2.Classes.PropertyDeclaration
        = 'OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrderPayment';

    /**
     * Selects and loads order payment history.
     *
     * @param string $orderId Order id.
     */
    public function load($orderId)
    {
        $oBaseObject = $this->getBaseObject();
        $sPaymentTable = $oBaseObject->getViewName();

        // we could not use the simple $oBaseObject->getSelectFields(), because the table has no necessary oxid
        $sSelect = "select
            $sPaymentTable.`oepaypal_paymentid`,
            $sPaymentTable.`oepaypal_action`,
            $sPaymentTable.`oepaypal_orderid`,
            $sPaymentTable.`oepaypal_amount`,
            $sPaymentTable.`oepaypal_refundedamount`,
            $sPaymentTable.`oepaypal_status`,
            $sPaymentTable.`oepaypal_date`,
            $sPaymentTable.`oepaypal_currency`,
            $sPaymentTable.`oepaypal_transactionid`,
            $sPaymentTable.`oepaypal_correlationid`
            from $sPaymentTable
            where $sPaymentTable.oepaypal_orderid = " .
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($orderId);

        $this->selectString($sSelect);
    }
}
