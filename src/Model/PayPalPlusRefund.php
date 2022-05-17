<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class paypPayPalPlusRefundData.
 * PayPal Plus refund data model.
 */
class PayPalPlusRefund extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    /**
     * Coretable name
     *
     * @var string
     */
    protected $_sCoreTable = 'payppaypalplusrefund'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Construct initialize class
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Get PayPal Plus related Payment  model sale (transaction) ID.
     *
     * @return string
     */
    public function getSaleId(): string
    {
        return $this->getFieldData('oxsaleid');
    }

    /**
     * Get PayPal Plus Refund model ID.
     *
     * @return string
     */
    public function getRefundId(): string
    {
        return $this->getFieldData('oxrefundid');
    }

    /**
     * Get PayPal Plus Refund model status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getFieldData('oxstatus');
    }

    /**
     * Get PayPal Plus Refund action date and time.
     *
     * @return string
     */
    public function getDateCreated(): string
    {
        return $this->getFieldData('oxdatecreated');
    }

    /**
     * Set PayPal Plus Refund model total (refunded) amount.
     *
     * @return float
     */
    public function getTotal(): float
    {
        return (float)$this->getFieldData('oxtotal');
    }

    /**
     * Get PayPal Plus Refund currency code related to the total amount.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getFieldData('oxcurrency');
    }

    /**
     * Get PayPal Plus Refund object un-serialized.
     *
     * @return object
     */
    public function getRefundObject(): false|object
    {
        $oRefundObject = unserialize(
            htmlspecialchars_decode(
                $this->getFieldData('oxrefundobject')
            )
        );
        return $oRefundObject;
    }

    /**
     * Load an instance by refund ID.
     *
     * @param string $sRefundId
     *
     * @return bool
     */
    public function loadByRefundId($sRefundId)
    {
        $db = DatabaseProvider::getDb();
        $sSelect = sprintf(
            "SELECT * FROM `%s` WHERE `OXREFUNDID` = %s",
            $this->getCoreTableName(),
            $db->quote($sRefundId)
        );

        return $this->assignRecord($sSelect);
    }
}
