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
use OxidSolutionCatalysts\PayPal\Helper\Truncate;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;

/**
 * Class PatchRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class   PatchRequestFactory
{
    /**
     * @var Basket
     */
    private $basket;

    /**
     * Returns array of patches that will be applied to an Order
     *
     * @param Basket $basket
     * @param string $orderId
     * @return array
     */
    public function getOrderPatches(
        Basket $basket,
        string $orderId = ''
    ): array {
        $this->basket = $basket;
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);

        $patches = array_values(
            array_filter([
                $this->getAmountPatch(),
                $orderId ? $this->getCustomIdPatch($orderId) : null,
                $this->getPurchaseUnitsPatch()
            ])
        );

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $patches[] = $this->getShippingNamePatch($deliveryAddress);
            $patches[] = $this->getShippingAddressPatch($deliveryAddress);
        }

        return $patches;
    }

    public function getShippingAddressPatch(Address $deliveryAddress): Patch
    {
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

        return $patch;
    }

    public function getShippingNamePatch(Address $deliveryAddress): ?Patch
    {
        $fullName = $deliveryAddress->oxaddress__oxfname->value . " " . $deliveryAddress->oxaddress__oxlname->value;
        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='"
            . Constants::PAYPAL_ORDER_REFERENCE_ID
            . "'/shipping/name";
        $patch->value = new \stdClass();
        $patch->value->full_name = $fullName;

        return $patch;
    }

    public function getAmountPatch(): ?Patch
    {
        $value = (Registry::get(PayPalRequestAmountFactory::class))->getAmount($this->basket);
        if ((float)$value->value !== 0.00) {
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/amount";
            $patch->value = $value;

            return $patch;
        }

        return null;
    }

    /**
     * @return \OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch|null
     */
    public function getPurchaseUnitsPatch(): ?Patch
    {
        $withItems = !$this->basket->isCalculationModeNetto();
        //update currency object with decimal precision restricted version
        $currency = $this->basket->getBasketCurrency();
        $currency->decimal = 2;

        if (!$withItems) {
            return null;
        }

        $patchValues = [];
        $language = Registry::getLang();

        $basketItems = $this->basket->getContents();
        /** @var BasketItem $basketItem */
        foreach ($basketItems as $basketItem) {
            $item = new Item();
            $item->name = (new Truncate())->truncate($basketItem->getTitle());
            $itemUnitPrice = $basketItem->getUnitPrice();
            if ($itemUnitPrice) {
                $item->unit_amount = PriceToMoney::convert(
                    $itemUnitPrice,
                    $currency
                );
                // We provide no tax, because Tax is in 99% not necessary.
                // Maybe just PUI, but PUI orders will not be patched.
                $item->quantity = (string)$basketItem->getAmount();
                $patchValues[] = $item;
            }
        }

        $wrapping = $this->basket->getPayPalCheckoutWrapping();
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

        $giftCard = $this->basket->getPayPalCheckoutGiftCard();
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

        $payment = $this->basket->getPayPalCheckoutPayment();
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

        // possible price surcharge
        $discount = $this->basket->getPayPalCheckoutDiscount();

        if ($discount < 0) {
            $discount *= -1;
            $item = new Item();
            $item->name = $language->translateString('SURCHARGE');

            $item->unit_amount = PriceToMoney::convert($discount, $currency);

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        // Dummy-Article for Rounding-Error
        if ($roundDiff = $this->basket->getPayPalCheckoutRoundDiff()) {
            $item = new Item();
            $item->name = $language->translateString('OSC_PAYPAL_VAT_CORRECTION');

            $item->unit_amount = PriceToMoney::convert((float)$roundDiff, $currency);

            $item->quantity = '1';
            $patchValues[] = $item;
        }

        if (!count($patchValues)) {
            return null;
        }

        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/items";

        $patch->value = $patchValues;

        return $patch;
    }

    public function getCustomIdPatch(string $shopOrderId): Patch
    {
        $patch = new Patch();
        $patch->op = Patch::OP_ADD;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/custom_id";
        $patch->value = $shopOrderId;

        return $patch;
    }

    /**
     * @param \OxidEsales\Eshop\Application\Model\Basket $basket
     */
    public function setBasket(Basket $basket): void
    {
        $this->basket = $basket;
    }
}
