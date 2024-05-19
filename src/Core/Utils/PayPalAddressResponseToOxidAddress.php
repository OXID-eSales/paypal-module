<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use OxidEsales\Eshop\Application\Model\Country;
use OxidSolutionCatalysts\PayPal\Model\Address;
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
        $country = oxNew(Country::class);
        $shippingAddress = null;
        $shippingFullName = '';
        $street = '';
        $streetNo = '';
        $countryId = '';
        $countryName = '';
        /** @var ShippingDetail $shippingDetail */
        $shippingDetail = $response->purchase_units[0]->shipping;
        if ($shippingDetail instanceof ShippingDetail) {
            $shippingAddress = $shippingDetail->address;
            $name = $shippingDetail->name;
            if ($name) {
                $shippingFullName = $name->full_name;
            }
        }
        $payer = $response->payer;

        if ($shippingAddress) {
            $countryId = $country->getIdByCode($shippingAddress->country_code);
            $country->load($countryId);
            if (isset($country->oxcountry__oxtitle)) {
                $countryName = $country->oxcountry__oxtitle->value;
            }

            $streetNo = '';
            try {
                $streetTmp = $shippingAddress->address_line_1;
                if (isset($streetTmp)) {
                    $addressData = AddressSplitter::splitAddress($streetTmp);
                }
                $street = $addressData['streetName'] ?? '';
                $streetNo = $addressData['houseNumber'] ?? '';
            } catch (SplittingException $e) {
                // The Address could not be split
                $street = $streetTmp;
            }
        }
        $fon = $payer->phone->phone_number->national_number ?? null;
        $address_line_2 = $shippingAddress->address_line_2 ?? '';
        $admin_area_2 = $shippingAddress->admin_area_2 ?? '';
        $postal_code = $shippingAddress->postal_code ?? '';

        return [
            $DBTablePrefix . 'fname' => self::getFirstName((string)$shippingFullName),
            $DBTablePrefix . 'lname' => self::getLastName((string)$shippingFullName),
            $DBTablePrefix . 'street' => $street,
            $DBTablePrefix . 'streetnr' => $streetNo,
            $DBTablePrefix . 'addinfo' => $address_line_2,
            $DBTablePrefix . 'city' => $admin_area_2,
            $DBTablePrefix . 'countryid' => $countryId,
            $DBTablePrefix . 'country' => $countryName,
            $DBTablePrefix . 'zip' => $postal_code,
            $DBTablePrefix . 'fon' => $fon,
        ];
    }

    protected static function getFirstName(string $name): string
    {
        return implode(' ', array_slice(explode(' ', (string) $name), 0, -1));
    }

    protected static function getLastName(string $name): string
    {
        return array_slice(explode(' ', (string) $name), -1)[0];
    }
}
