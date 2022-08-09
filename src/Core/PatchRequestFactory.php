<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;

/**
 * Class PatchRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PatchRequestFactory
{
    /**
     * @var Array
     */
    private $request = [];

    /**
     * @var Basket
     */
    private $basket;

    /**
     * @param Basket $basket
     *
     * @return array
     */
    public function getRequest(
        Basket $basket,
        string $orderId = ''
    ): array {
        $this->basket = $basket;

        $this->getShippingNamePatch();
        $this->getShippingAddressPatch();
        $this->getAmountPatch();
        if ($orderId) {
            $this->getCustomIdPatch($orderId);
        }

        /** @var BasketItem $basketItem */
        // PayPal cannot fully patch the items in the shopping cart.
        // At the moment only the amount and the title of the article
        // are relevant. However, no inventory.
        // So we ignore the Article-Patch
        //foreach ($this->basket->getContents() as $basketItem) {
        //    $this->getPurchaseUnitsPatch($basketItem);
        //}

        return $this->request;
    }

    /**
     * @return Patch|null
     */
    protected function getShippingAddressPatch(): ?Patch
    {
        $patch = null;

        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='"
                . Constants::PAYPAL_ORDER_REFERENCE_ID
                . "'/shipping/address";

            $address = new AddressPortable();

            $state = oxNew(State::class);
            $state->load($deliveryAddress->getFieldData('oxstateid'));

            $country = oxNew(Country::class);
            $country->load($deliveryAddress->getFieldData('oxcountryid'));

            $addressLine =
                $deliveryAddress->getFieldData('oxstreet') . " " . $deliveryAddress->getFieldData('oxstreetnr');
            $address->address_line_1 = $addressLine;
            $address->admin_area_1 = $state->getFieldData('oxtitle');
            $address->admin_area_2 = $deliveryAddress->getFieldData('oxcity');
            $address->country_code = $country->oxcountry__oxisoalpha2->value;
            $address->postal_code = $deliveryAddress->getFieldData('oxzip');

            $patch->value = $address;
        }
        return $patch;
    }

    protected function getShippingNamePatch(): void
    {
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $fullName = $deliveryAddress->oxaddress__oxfname->value . " " . $deliveryAddress->oxaddress__oxlname->value;
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='"
                . Constants::PAYPAL_ORDER_REFERENCE_ID
                . "'/shipping/name";
            $patch->value = new \stdClass();
            $patch->value->full_name = $fullName;

            $this->request[] = $patch;
        }
    }

    protected function getAmountPatch(): void
    {
        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/amount";

        $amount = new AmountWithBreakdown();
        $currency = $this->basket->getBasketCurrency();

        $total = PriceToMoney::convert($this->basket->getPrice()->getBruttoPrice(), $currency);

        //Total amount
        $amount->value = $total->value;
        $amount->currency_code = $total->currency_code;
        //Cost breakdown
        $breakdown = $amount->breakdown = new AmountBreakdown();

        //Item total cost
        $itemTotal = (float)$this->basket->getPayPalCheckoutItems();
        $breakdown->item_total = PriceToMoney::convert($itemTotal, $currency);
        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        //Discount
        if ($discount = $this->basket->getPayPalCheckoutDiscount()) {
            $breakdown->discount = PriceToMoney::convert((float)$discount, $currency);
        }
        $patch->value = $amount;

        $this->request[] = $patch;
    }

    /**
     * @param BasketItem $basketItem
     * @param bool $nettoPrices
     * @param $currency
     */
    protected function getPurchaseUnitsPatch(
        BasketItem $basketItem
    ): void {
        $currency = $this->basket->getBasketCurrency();

        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/items";

        $item = new Item();
        $item->name = $basketItem->getTitle();
        $itemUnitPrice = $basketItem->getUnitPrice();
        $item->unit_amount = PriceToMoney::convert((float)$itemUnitPrice->getBruttoPrice(), $currency);

        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $item->tax = PriceToMoney::convert(0, $currency);

        $item->quantity = (string) $basketItem->getAmount();

        $patch->value = $item;

        $this->request[] = $patch;
    }

    protected function getCustomIdPatch(string $shopOrderId): void
    {
        $patch = new Patch();
        $patch->op = Patch::OP_ADD;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/custom_id";
        $patch->value = $shopOrderId;

        $this->request[] = $patch;
    }
}
