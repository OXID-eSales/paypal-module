<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Traits\CustomerAddressHelper;

/**
 * Used to generate purchase_units that are transfered in JS request to create PayPal Order
 *
 * Class PayPalRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PayPalPurchaseUnitsFactory
{
    use ServiceContainer;
    use CustomerAddressHelper;

    /**
     * @var object|\OxidEsales\Eshop\Application\Model\Basket|null
     */
    private $basket;

    public function getPurchaseUnits(): string
    {
        $this->basket = Registry::getSession()->getBasket();

        if (null === $this->basket) {
            return '';
        }

        $patchRequestFactory = Registry::get(PatchRequestFactory::class);
        $patchRequestFactory->setBasket($this->basket);
        $purchaseUnitsPatch = ($patchRequestFactory)->getPurchaseUnitsPatch();

        $withItems = !$this->basket->isCalculationModeNetto();
        $amount = (Registry::get(PayPalRequestAmountFactory::class))->getAmount($this->basket);

        $purchaseUnits = [
            "invoice_id" => "",
            "amount" => [
                "currency_code" => $amount->currency_code,
                "value" => $amount->value,
                "breakdown" => json_decode(json_encode($amount->breakdown), true)
            ],
        ];

        if ($withItems) {
            $items = [];

            foreach ($purchaseUnitsPatch->value as $orderItem) {
                $orderItem = (array)$orderItem;

                $item = [
                    "name" => $orderItem["name"],
                    "quantity" => (string)($orderItem["quantity"] ?? 1),
                    "unit_amount" => (array)$orderItem["unit_amount"]
                ];

                if (!empty($orderItem["description"])) {
                    $item['description'] = $orderItem["description"];
                }
                if (!empty($orderItem["category"])) {
                    $item['category'] = $orderItem["category"];
                }
                if (!empty($orderItem["sku"])) {
                    $item['sku'] = $orderItem["sku"];
                }

                $items[] = $item;
            }

            $purchaseUnits['items'] = $items;
        }

        $purchaseUnits = $this->addDeliveryAddress($purchaseUnits);

        return json_encode($purchaseUnits);
    }

    /**
     * @param array $purchaseUnits
     * @return array
     */
    public function addDeliveryAddress(array $purchaseUnits): array
    {
        $user = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        if ($user->loadActiveUser()) {
            $country = $this->getCountryFromBasket($this->basket);

            $shipping = [
                'name' => [
                    'full_name' => $user->oxuser__oxfname . ' ' . $user->oxuser__oxlname
                ],

                'address' => [
                    'address_line_1' => $user->oxuser__oxstreet->value,
                    'address_line_2' => $user->oxuser__oxstreetnr->value,
                    'admin_area_2' => $user->oxuser__oxcity->value,
                    'admin_area_1' => $user->oxuser__oxstateid->value,
                    'postal_code' => $user->oxuser__oxzip->value,
                    'country_code' => $country->getFieldData('oxisoalpha2'),
                ]
            ];
            $purchaseUnits['shipping'] = $shipping;
        }

        return $purchaseUnits;
    }
}
