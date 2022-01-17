<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use OxidEsales\Eshop\Application\Model\Country;
use \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrderModel;
use VIISON\AddressSplitter\AddressSplitter;
use VIISON\AddressSplitter\Exceptions\SplittingException;

class PayPalAddressResponseToOxidAddress
{
    /**
     * @param obj $response PayPal Response
     * @param string $DBTablePrefix
     * @return array
     */
    public static function mapAddress(
        PayPalApiOrderModel $response,
        string $DBTablePrefix
    ): array {
        $country = oxNew(Country::class);
        $countryId = $country->getIdByCode($response->purchase_units[0]->shipping->address->country_code);
        $country->load($countryId);
        $countryName = $country->oxcountry__oxtitle->value;
        $street = '';
        $streetNo = '';
        try {
            $streetTmp = $response->purchase_units[0]->shipping->address->address_line_1;
            $addressData = AddressSplitter::splitAddress($streetTmp);
            $street = $addressData['streetName'] ?? '';
            $streetNo = $addressData['houseNumber'] ?? '';
        } catch (SplittingException $e) {
            // The Address could not be split
            $street = $streetTmp;
        }

        return [
            $DBTablePrefix . 'oxfname' => $response->payer->name->given_name,
            $DBTablePrefix . 'oxlname' => $response->payer->name->surname,
            $DBTablePrefix . 'oxstreet' => $street,
            $DBTablePrefix . 'oxstreetnr' => $streetNo,
            $DBTablePrefix . 'oxcity' => $response->purchase_units[0]->shipping->address->admin_area_2,
            $DBTablePrefix . 'oxcountryid' => $countryId,
            $DBTablePrefix . 'oxcountry' => $countryName,
            $DBTablePrefix . 'oxzip' => $response->purchase_units[0]->shipping->address->postal_code,
        ];
    }
}
