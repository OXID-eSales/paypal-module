<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * PayPal Plus Refund data list manager class.
 * Collects a list of refunds according to payment sale ID.
 */
class PayPalPlusRefundList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName // phpcs:ignore PSR2.Classes.PropertyDeclaration
        = 'OxidSolutionCatalysts\PayPal\Model\PayPalPlusRefund';

    /**
     * Load PayPal Plus refund models by sale ID and orders them by creation date and time.
     *
     * @param string $sSaleId
     */
    public function loadRefundsBySaleId($sSaleId)
    {
        $db = DatabaseProvider::getDb();

        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `OXSALEID` = %s ORDER BY `OXDATECREATED`",
            $this->getBaseObject()->getCoreTableName(),
            $db->quote($sSaleId)
        );

        $this->selectString($sSelect);
    }

    /**
     * Count and return a sum of all totals for refunds related to a given sale ID.
     * In other words, it counts already refunded total amount for a payment.
     *
     * @param string $sSaleId
     *
     * @return float
     */
    public function getRefundedSumBySaleId($sSaleId): float
    {
        $db = DatabaseProvider::getDb();

        $sQuery = sprintf(
            "SELECT SUM(`OXTOTAL`) FROM `%s` WHERE `OXSALEID` = %s",
            $this->getBaseObject()->getCoreTableName(),
            $db->quote($sSaleId)
        );

        return (float)$db->getOne($sQuery);
    }
}
