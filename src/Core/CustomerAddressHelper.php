<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;

trait CustomerAddressHelper
{
    protected function getUserNameFromBasket($basket): string
    {
        $user = $basket->getBasketUser();
        return $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname');
    }

    protected function getCountryFromBasket($basket): Country
    {
        $user = $basket->getBasketUser();
        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $country->load($deliveryAddress->getFieldData('oxcountryid'));
        }

        return $country;
    }
}
