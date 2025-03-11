<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\src\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;
use stdClass;

/**
 * Used to generate purchase_units that are transfered in JS request to create PayPal Order
 *
 * Class PayPalRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PayPalPurchaseUnitsFactory
{

    public function getPurchaseUnits(): string
    {
        $basket = Registry::getSession()->getBasket();
        $purchaseUnitsPatch = (Registry::get(PatchRequestFactory::class))->getPurchaseUnitsPatch($basket);
        $currency = Registry::getConfig()->getActShopCurrencyObject();
        $withItems = !$basket->isCalculationModeNetto() && !($currency->decimal > 2);
        $amount = (Registry::get(PayPalRequestAmountFactory::class))->getAmount($basket);

        $purchaseUnits = [
            "invoice_id" => "",
            "amount" => [
                "currency_code" => $amount->currency_code,
                "value" => $amount->value,
                "breakdown" => json_decode(json_encode($amount->breakdown), true)
            ],
        ];

        if($withItems){
            $items = [];

            foreach ($purchaseUnitsPatch->value as $orderItem){
                $orderItem = (array)$orderItem;
                $items[] = [
                    "name" => $orderItem["name"],
                    "unit_amount" => (array)$orderItem["unit_amount"],
                    "quantity" => (int)$orderItem["quantity"] ?? 1,
                    "description" => $orderItem["description"] ?? "",
                    "category" => $orderItem["category"] ?? "",
                    "sku" => $orderItem["sku"] ?? "",
                ];
            }

         //   $purchaseUnits['items'] = $items;
        }

        return json_encode($purchaseUnits);
    }
}
