<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;

/**
 * Class PatchRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class PatchRequestFactory
{
    use ServiceContainer;

    /**
     * @var Basket
     */
    private $basket;

    /**
     * Returns array of patches that will be applied to an Order
     *
     * @param Basket $basket
     * @param Order|null $order
     * @return array
     */
    public function getOrderPatches(
        Basket $basket,
        ?Order $order = null
    ): array {
        $this->basket = $basket;
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);

        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $patches = array_values(
            array_filter([
                $this->getAmountPatch(),
                $order ? $this->getCustomIdPatch($paymentService->getCustomIdParameter($order)) : null,
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
        // Build amount using the same logic as PurchaseUnitsFactory (items-first + validator)
        /** @var PayPalPurchaseUnitsFactory $puFactory */
        $puFactory = $this->getServiceFromContainer(PayPalPurchaseUnitsFactory::class);
        // We want items considered so that tax_total and validator adjustments are consistent
        $puFactory->setBasket($this->basket);
        $units = $puFactory->getPurchaseUnits(null, null, true);
        $unit = $units[0] ?? null;
        $value = $unit ? $unit->amount : null;

        if ($value && (float)$value->value !== 0.00) {
            $patch = new Patch();
            $patch->op = Patch::OP_REPLACE;
            $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/amount";
            $patch->value = $value;

            return $patch;
        }

        return null;
    }

    /**
     * @return Patch|null
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws NoArticleException
     */
    public function getPurchaseUnitsPatch(): ?Patch
    {
        // Reuse the same purchase-units factory as getAmountPatch(), so the patched items
        // stay consistent with the patched amount (item_total / tax_total) in both net and
        // gross calculation mode. The factory itemises products, payment surcharge, gift
        // wrapping and greeting card (each with correct per-item tax), applies the whole-number
        // quantity guard and runs PayPalAmountValidator internally — so the former net-mode
        // short-circuit (!isCalculationModeNetto()) and the separate, hand-built item list are
        // no longer needed here. Only true cent rounding is still balanced by the validator.
        /** @var PayPalPurchaseUnitsFactory $puFactory */
        $puFactory = $this->getServiceFromContainer(PayPalPurchaseUnitsFactory::class);
        $puFactory->setBasket($this->basket);
        $units = $puFactory->getPurchaseUnits(null, null, true);
        $unit = $units[0] ?? null;
        $items = ($unit && !empty($unit->items)) ? $unit->items : [];

        // No items to patch (e.g. decimal quantities disabled items in the factory).
        if (empty($items)) {
            return null;
        }

        $patch = new Patch();
        $patch->op = Patch::OP_REPLACE;
        $patch->path = "/purchase_units/@reference_id=='" . Constants::PAYPAL_ORDER_REFERENCE_ID . "'/items";
        $patch->value = $items;

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
