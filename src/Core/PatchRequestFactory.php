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
use OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;

/**
 * Class PatchRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PatchRequestFactory
{
    use RequestDataGetter;

    private array $request = [];

    private Basket $basket;

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

        return $this->request;
    }

    protected function getShippingAddressPatch(): void
    {
        $deliveryId = self::getRequestStringParameter("deladrid");
        /** @var \OxidSolutionCatalysts\PayPal\Model\Address $deliveryAddress */
        $deliveryAddress = oxNew(Address::class);

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='"
                . Constants::PAYPAL_ORDER_REFERENCE_ID
                . "'/shipping/address";

            $address = new AddressPortable();

            /** @var \OxidSolutionCatalysts\PayPal\Model\State $state */
            $state = oxNew(State::class);
            $state->load($deliveryAddress->getPaypalStringData('oxstateid'));

            $country = oxNew(Country::class);
            $country->load($deliveryAddress->getPaypalStringData('oxcountryid'));

            $addressLine =
                $deliveryAddress->getPaypalStringData('oxstreet') . " " . $deliveryAddress->getPaypalStringData('oxstreetnr');
            $address->address_line_1 = $addressLine;

            $addinfoLine = $deliveryAddress->getPaypalStringData('oxcompany') . " " .
                $deliveryAddress->getPaypalStringData('oxaddinfo');
            $address->address_line_2 = $addinfoLine;

            $address->admin_area_1 = $state->getPaypalStringData('oxtitle');
            $address->admin_area_2 = $deliveryAddress->getPaypalStringData('oxcity');
            if (isset($country->oxcountry__oxisoalpha2)) {
                $address->country_code = $country->oxcountry__oxisoalpha2->value;
            }
            $address->postal_code = $deliveryAddress->getPaypalStringData('oxzip');

            $patch->value = $address;

            $this->request[] = $patch;
        }
    }

    protected function getShippingNamePatch(): void
    {
        $deliveryId = self::getRequestStringParameter("deladrid");
        $deliveryAddress = oxNew(Address::class);

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $fullName = '';
            if (isset($deliveryAddress->oxaddress__oxfname) && isset($deliveryAddress->oxaddress__oxlname)) {
                $fullName = $deliveryAddress->oxaddress__oxfname->value
                            . " " . $deliveryAddress->oxaddress__oxlname->value;
            }
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
        $patch->value = (Registry::get(PayPalRequestAmountFactory::class))->getAmount($this->basket);

        $this->request[] = $patch;
    }

    /**
     * @param BasketItem $basketItem
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
