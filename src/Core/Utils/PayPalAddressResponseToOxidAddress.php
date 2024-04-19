<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use OxidEsales\Eshop\Application\Model\Country;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ShippingDetail;
use VIISON\AddressSplitter\AddressSplitter;
use VIISON\AddressSplitter\Exceptions\SplittingException;

class PayPalAddressResponseToOxidAddress
{
    /**
     * @param PayPalApiOrderModel $response PayPal Response
     * @return array
     */
    public static function mapOrderDeliveryAddress(
        PayPalApiOrderModel $response
    ): array {
        return self::mapAddress(
            $response,
            'oxorder__oxdel'
        );
    }

    /**
     * @param PayPalApiOrderModel $response PayPal Response
     * @return array
     */
    public static function mapUserDeliveryAddress(
        PayPalApiOrderModel $response
    ): array {
        return self::mapAddress(
            $response,
            'oxaddress__ox'
        );
    }

    /**
     * @param PayPalApiOrderModel $response PayPal Response
     * @return array
     */
    public static function mapUserInvoiceAddress(
        PayPalApiOrderModel $response
    ): array {
        return self::mapAddress(
            $response,
            'oxuser__ox'
        );
    }

    /**
     * @param PayPalApiOrderModel $response PayPal Response
     * @param string $DBTablePrefix
     * @return array
     */
    private static function mapAddress(
        PayPalApiOrderModel $response,
        string $DBTablePrefix
    ): array {
        $payer = $response->payer;
        $country = oxNew(Country::class);
        $countryName = '';
        $countryId = '';
        $street = '';
        $streetNo = '';
        $shippingAddress = '';
        $shippingFullName = '';
        $shippingDetail = $response->purchase_units[0]->shipping;
        if ($shippingDetail) {
            $shippingAddress = $shippingDetail->address;
            $shippingFullName = $shippingDetail->name?->full_name;
            if (isset($shippingAddress) && property_exists($shippingAddress, 'country_code')) {
                $countryId = $country->getIdByCode($shippingAddress->country_code);
                $country->load($countryId);
                if (!empty($country->oxcountry__oxtitle)) {
                    $countryName = $country->oxcountry__oxtitle->value;
                }
            }
        }

        try {
            if (!empty($shippingAddress)) {
                $streetTmp = $shippingAddress->address_line_1;
                $addressData = AddressSplitter::splitAddress((string)$streetTmp);
                $street = $addressData['streetName'] ?? '';
                $streetNo = $addressData['houseNumber'] ?? '';
            }
        } catch (SplittingException $e) {
            // The Address could not be split
            $street = $streetTmp;
        }

        return [
            $DBTablePrefix . 'fname' => self::getFirstName((string)$shippingFullName),
            $DBTablePrefix . 'lname' => self::getLastName((string)$shippingFullName),
            $DBTablePrefix . 'street' => $street,
            $DBTablePrefix . 'streetnr' => $streetNo,
            $DBTablePrefix . 'addinfo' => $shippingAddress->address_line_2 ?? '',
            $DBTablePrefix . 'city' => $shippingAddress->admin_area_2 ?? '',
            $DBTablePrefix . 'countryid' => $countryId,
            $DBTablePrefix . 'country' => $countryName,
            $DBTablePrefix . 'zip' => $shippingAddress->postal_code ?? '',
            $DBTablePrefix . 'fon' => $payer->phone->phone_number->national_number ?? '',
            // Needed to not produce an error in InputValidator->hasRequiredParametersForVatInCheck()
            $DBTablePrefix . 'ustid' => '',
            // Needed to not produce an error in InputValidator->hasRequiredParametersForVatInCheck()
            $DBTablePrefix . 'company' => '',
        ];
    }

    protected static function getFirstName(string $name): string
    {
        return implode(' ', array_slice(explode(' ', $name), 0, -1));
    }

    protected static function getLastName(string $name): string
    {
        return array_slice(explode(' ', $name), -1)[0];
    }
}
