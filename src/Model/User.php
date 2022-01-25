<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\RequiredAddressFields;

/**
 * PayPal oxOrder class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class User extends User_parent
{
    /**
     * @return false if User has no subscriped the product
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function hasSubscribed($subscriptionPlanId = '')
    {
        $select = 'SELECT osc_paypal_subscription.`oxid`
            FROM osc_paypal_subscription
            LEFT JOIN osc_paypal_subscription_product
            ON (osc_paypal_subscription.`oxpaypalsubprodid` = osc_paypal_subscription_product.`oxid`)
            WHERE osc_paypal_subscription.`oxuserid` = ?
            AND osc_paypal_subscription_product.`paypalsubscriptionplanid` = ?';

        $result = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow(
            $select,
            [
                $this->getId(),
                $subscriptionPlanId
            ]
        );

        return $result ? true : false;
    }

    /**
     * get the InvoiceAddress from user with all required fields
     * @return array
     */
    public function getInvoiceAddress()
    {
        $result = [];
        $requiredAddressFields = oxNew(RequiredAddressFields::class);
        foreach ($requiredAddressFields->getBillingFields() as $requiredAddressField) {
            $result[$requiredAddressField] = $this->{$requiredAddressField}->value;
        }

        return $result;
    }
}
