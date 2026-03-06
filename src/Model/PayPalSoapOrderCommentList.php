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
    public function load($paymentId)
    {
        $oBaseObject = $this->getBaseObject();
        $sPaymentTable = $oBaseObject->getViewName();

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sPaymentTable)) {
            throw new \InvalidArgumentException('Invalid view name');
        }

        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sSelect = "select
            `{$sPaymentTable}`.`oepaypal_commentid`,
            `{$sPaymentTable}`.`oepaypal_paymentid`,
            `{$sPaymentTable}`.`oepaypal_date`,
            `{$sPaymentTable}`.`oepaypal_comment`
            from `{$sPaymentTable}`
            where `{$sPaymentTable}`.`oepaypal_paymentid` = " .
            $db->quote($paymentId);

        $this->selectString($sSelect);
    }
}
