<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\RequiredAddressFields;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;


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
        $select = 'SELECT oscpaypal_subscription.`oxid`
            FROM oscpaypal_subscription
            LEFT JOIN oscpaypal_subscription_product
            ON (oscpaypal_subscription.`oxpaypalsubprodid` = oscpaypal_subscription_product.`oxid`)
            WHERE oscpaypal_subscription.`oxuserid` = ?
            AND oscpaypal_subscription_product.`paypalsubscriptionplanid` = ?';

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


    /**
     * @param string $userName
     *
     * @return false|string
     */
    private function getUserIdByPayPalAddress(string $userName)
    {
        return DatabaseProvider::getDb()->getOne(
            "SELECT `OXID` FROM oxuser
            WHERE 1 AND oxusername = :oxusername",
            [
                ':oxusername' => $userName
            ]
        );
    }

    /**
     * Login with PayPalUsername
     *
     * @param string $userName
     * @param string $password
     */
    protected function onLogin($userName, $password)
    {
        if (PayPalSession::isPayPalOrderActive()) {
            $userId = $this->getUserIdByPayPalAddress($userName);
            if ($userId) {
                $this->load($userId);
            }
        } else {
            parent::onLogin($userName, $password);
        }
    }
}
