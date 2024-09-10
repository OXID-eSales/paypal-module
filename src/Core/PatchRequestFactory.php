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
     * @var array
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
        $withItems = !$this->basket->isCalculationModeNetto();
        $currency = $basket->getBasketCurrency();

        $this->getShippingNamePatch();
        $this->getShippingAddressPatch();
        $this->getAmountPatch();
        if ($orderId) {
            $this->getCustomIdPatch($orderId);
        }
        if ($withItems) {
            $this->getPurchaseUnitsPatch(
                $this->basket,
                $currency
            );
        }

        return $this->request;
    }

    protected function getShippingAddressPatch(): void
    {
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

            $addinfoLine = $deliveryAddress->getFieldData('oxcompany') . " " .
                $deliveryAddress->getFieldData('oxaddinfo');
            $address->address_line_2 = $addinfoLine;

            $address->admin_area_1 = $state->getFieldData('oxtitle');
            $address->admin_area_2 = $deliveryAddress->getFieldData('oxcity');
            $address->country_code = $country->oxcountry__oxisoalpha2->value;
            $address->postal_code = $deliveryAddress->getFieldData('oxzip');

            $patch->value = $address;

            $this->request[] = $patch;
        }
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
        $value = (Registry::get(PayPalRequestAmountFactory::class))->getAmount($this->basket);
        if ((float)$value->value !== 0.00) {
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/amount";
            $patch->value = $value;

            $this->request[] = $patch;
        }
    }

    /**
     * @param Basket $basket
     * @param $currency
     */
    protected function getPurchaseUnitsPatch(
        Basket $basket,
        $currency
    ): void {

        $basketItems = $basket->getContents();
        $language = Registry::getLang();

        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/items";
        $patchValues = [];

        /** @var BasketItem $basketItem */
        foreach ($basketItems as $basketItem) {
            $item = new Item();
            $item->name = $basketItem->getTitle();
            $itemUnitPrice = $basketItem->getUnitPrice();
            if ($itemUnitPrice) {
                $item->unit_amount = PriceToMoney::convert(
                    $itemUnitPrice,
                    $currency
                );
                // We provide no tax, because Tax is in 99% not necessary.
                // Maybe just PUI, but PUI orders will not be patched.
                $item->quantity = (string) $basketItem->getAmount();
                $patchValues[] = $item;
            }
        }

        $wrapping = $basket->getPayPalCheckoutWrapping();
        if ($wrapping) {
            $item = new Item();
            $item->name = $language->translateString('GIFT_WRAPPING');

            $item->unit_amount = PriceToMoney::convert(
                $wrapping,
                $currency
            );

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        $giftCard = $basket->getPayPalCheckoutGiftCard();
        if ($giftCard) {
            $item = new Item();
            $item->name = $language->translateString('GREETING_CARD');

            $item->unit_amount = PriceToMoney::convert(
                $giftCard,
                $currency
            );

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        $payment = $basket->getPayPalCheckoutPayment();
        if ($payment) {
            $item = new Item();
            $item->name = $language->translateString('PAYMENT_METHOD');

            $item->unit_amount = PriceToMoney::convert(
                $payment,
                $currency
            );

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        //Shipping cost
        $delivery = $basket->getPayPalCheckoutDeliveryCosts();
        if ($delivery) {
            $item = new Item();
            $item->name = $language->translateString('SHIPPING_COST');

            $item->unit_amount = PriceToMoney::convert(
                $delivery,
                $currency
            );

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        // possible price surcharge
        $discount = $basket->getPayPalCheckoutDiscount();

        if ($discount < 0) {
            $discount *= -1;
            $item = new Item();
            $item->name = $language->translateString('SURCHARGE');

            $item->unit_amount = PriceToMoney::convert($discount, $currency);

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        // Dummy-Article for Rounding-Error
        if ($roundDiff = $basket->getPayPalCheckoutRoundDiff()) {
            $item = new Item();
            $item->name = $language->translateString('OSC_PAYPAL_VAT_CORRECTION');

            $item->unit_amount = PriceToMoney::convert((float)$roundDiff, $currency);

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        $patch->value = $patchValues;

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
