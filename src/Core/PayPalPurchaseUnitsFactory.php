<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\src\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Controller\PaymentController;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;

/**
 * Used to generate purchase_units that are transfered in JS request to create PayPal Order
 *
 * Class PayPalRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PayPalPurchaseUnitsFactory
{
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

        return json_encode($purchaseUnits);
    }
}
