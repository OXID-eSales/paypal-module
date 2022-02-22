<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class PayPalArticle
 * created to manage the link between the oxid article and the paypal subscribable product
 * @mixin Article
 */
class Article extends Article_parent
{
    /**
     * PayPalProduct ID
     *
     * @var string
     */
    protected $_sPayPalProductId = null;


    /**
     * @return bool
     */
    public function isPayPalProductLinked()
    {
        return ($this->getPayPalProductId() !== "");
    }

    /**
     * @return string
     */
    public function getPayPalProductId(): string
    {
        //TODO: subscription model?, use querybuilder

        if (is_null($this->_sPayPalProductId)) {
            $this->_sPayPalProductId = '';

            $sql = 'SELECT PAYPALPRODUCTID
                FROM oscpaypal_subscription_product
                WHERE OXARTID = ?';

            $sPayPalProductId = (string) DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
            ->getOne(
                $sql,
                [
                    $this->getId()
                ]
            );

            if ($sPayPalProductId) {
                $this->_sPayPalProductId = $sPayPalProductId;
            }
        }
        return $this->_sPayPalProductId;
    }
}
