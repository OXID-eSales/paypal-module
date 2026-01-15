<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service\Factory;

use OxidEsales\Eshop\Application\Model\Address as EshopAddress;
use OxidEsales\Eshop\Application\Model\Country as EshopCountry;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item as ApiItem;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest as ApiPurchaseUnitRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ShippingDetail as ApiShippingDetail;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable3 as ApiAddressPortable3;
use OxidSolutionCatalysts\PayPal\Service\PayPalAmountValidator;
use stdClass;
use Throwable;

/**
 * PayPalPurchaseUnitsFactory
 *
 * Responsibility: Build only the PayPal purchase_units array.
 * This class contains all mechanisms required to create purchase units
 * directly from the current basket, without using BasketOrderDataMapper.
 * Other request-related parts (payer, shipping address objects, experience
 * context, etc.) are handled elsewhere.
 */
class PayPalPurchaseUnitsFactory
{
    private const DECIMALS = 2;
    /**
     * @var ModuleSettings
     */
    private ModuleSettings $moduleSettings;
    /**
     * @var Basket|null
     */
    private ?Basket $basket = null;

    public function __construct(ModuleSettings $moduleSettings)
    {
        $this->moduleSettings = $moduleSettings;
    }

    /**
     * Build and return a PurchaseUnitRequest API model for the current basket.
     * Mirrors OrderRequestFactory::getPurchaseUnits in principle.
     *
     * @return ApiPurchaseUnitRequest[]
     */
    public function getPurchaseUnits(
        ?string $transactionId = null,
        ?string $invoiceId = null,
        bool $withItems = true
    ): array {
        $basket = $this->getBasket();

        // Ensure PayPal compatible precision
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        // Check if any basket item has a decimal quantity
        // PayPal API only accepts whole numbers for item quantities
        if ($withItems) {
            foreach ($basket->getContents() as $basketItem) {
                $amount = $basketItem->getAmount();

                // Convert to float to handle both numeric and string values
                $amount = (float)$amount;

                if ($amount !== floor($amount)) {
                    $withItems = false;
                    break;
                }
            }
        }

        // Build items first (needed to compute tax_total from items)
        $itemsArr = $withItems ? $this->mapItems($basket) : [];
        // Build amount (tax_total will be computed from itemsArr)
        $amountArr = $this->buildAmountArray($basket, $itemsArr);
        // Adjust breakdown with PayPalAmountValidator to avoid rounding issues
        $adjusted = $this->applyAmountValidator($itemsArr, $amountArr);
        $amount = $this->mapAmountWithBreakdown($adjusted['amount']);
        $itemsArr = $adjusted['items'] ?? $itemsArr;

        // Items
        $items = [];
        if (!empty($itemsArr)) {
            foreach ($itemsArr as $it) {
                $apiItem = new ApiItem([
                    'name' => (string)($it['name'] ?? ''),
                    'sku' => (string)($it['sku'] ?? ''),
                    'unit_amount' => [
                        'currency_code' => (string)(
                            $it['unit_amount']['currency_code']
                                ?? ($amount->currency_code
                                    ?? ''
                        )),
                        'value' => (string)($it['unit_amount']['value'] ?? '0.00'),
                    ],
                    'quantity' => (string)($it['quantity'] ?? '1'),
                    'tax' => isset($it['tax']) ? [
                        'currency_code' => (string)($it['tax']['currency_code'] ?? ($amount->currency_code ?? '')),
                        'value' => (string)($it['tax']['value'] ?? '0.00'),
                    ] : null,
                    'tax_rate' => (string)($it['tax_rate'] ?? '0'),
                    'category' => (string)($it['category'] ?? ''),
                ]);
                $items[] = $apiItem;
            }
        }

        // Assemble unit
        $unit = new ApiPurchaseUnitRequest();
        $unit->reference_id = Constants::PAYPAL_ORDER_REFERENCE_ID;
        $unit->amount = $amount;
        if (!empty($items)) {
            $unit->items = $items;
        }

        // Add IDs and description similar to OrderRequestFactory
        if ($transactionId !== null) {
            $unit->custom_id = $transactionId;
        }
        if ($invoiceId !== null) {
            $unit->invoice_id = $invoiceId;
        }

        $shopName = $this->moduleSettings->getShopName();
        $lang = Registry::getLang();
        $unit->description = sprintf($lang->translateString('OSC_PAYPAL_DESCRIPTION'), $shopName);

        // Shipping address when user is present
        if ($basket->getBasketUser()) {
            $unit->shipping = $this->buildShippingDetail($basket);
        }

        return [$unit];
    }

    private function getBasket(): ?Basket
    {
        return null === $this->basket ? Registry::getSession()->getBasket() : $this->basket;
    }

    /**
     * Build ShippingDetail similar to OrderRequestFactory::getShippingAddress
     */
    private function buildShippingDetail(Basket $basket): ?ApiShippingDetail
    {
        $user = $basket->getBasketUser();
        if (!$user) {
            return null;
        }
        $deliveryId = Registry::getSession()->getVariable('deladrid');
        $deliveryAddress = new EshopAddress();
        $shipping = new ApiShippingDetail();
        $name = $shipping->initName();

        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $fullName = $deliveryAddress->oxaddress__oxfname->value . ' ' . $deliveryAddress->oxaddress__oxlname->value;
            $name->full_name = $fullName;

            $address = new ApiAddressPortable3();

            $state = oxNew(State::class);
            $state->loadByIdAndCountry(
                $deliveryAddress->getFieldData('oxstateid'),
                $deliveryAddress->getFieldData('oxcountryid')
            );

            $country = oxNew(EshopCountry::class);
            $country->load($deliveryAddress->getFieldData('oxcountryid'));

            $addressLine = $deliveryAddress->getFieldData('oxstreet')
                . ' '
                . $deliveryAddress->getFieldData('oxstreetnr');
            $address->address_line_1 = $addressLine;

            $addinfoLine = $deliveryAddress->getFieldData('oxcompany')
                . ' '
                . $deliveryAddress->getFieldData('oxaddinfo');
            $address->address_line_2 = $addinfoLine;

            $address->admin_area_1 = $state->getFieldData('oxtitle');
            $address->admin_area_2 = $deliveryAddress->getFieldData('oxcity');
            $address->country_code = $country->oxcountry__oxisoalpha2->value;
            $address->postal_code = $deliveryAddress->getFieldData('oxzip');

            $shipping->address = $address;
        } else {
            $fullName = $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname');
            $name->full_name = $fullName;
            $shipping->address = $this->buildBillingAddress($user);
        }

        return $shipping;
    }

    private function buildBillingAddress($user): ApiAddressPortable3
    {
        $state = oxNew(State::class);
        $state->loadByIdAndCountry(
            $user->getFieldData('oxstateid'),
            $user->getFieldData('oxcountryid')
        );

        $country = oxNew(EshopCountry::class);
        $country->load($user->getFieldData('oxcountryid'));

        $address = new ApiAddressPortable3();
        $address->address_line_1 = $user->getFieldData('oxstreet') . ' ' . $user->getFieldData('oxstreetnr');
        $address->address_line_2 = $user->getFieldData('oxcompany') . ' ' . $user->getFieldData('oxaddinfo');
        $address->admin_area_1 = $state->getFieldData('oxtitle');
        $address->admin_area_2 = $user->getFieldData('oxcity');
        $address->country_code = $country->oxcountry__oxisoalpha2->value;
        $address->postal_code = $user->getFieldData('oxzip');

        return $address;
    }

    /**
     * Build amount with breakdown directly from the basket.
     */
    private function buildAmountArray(Basket $basket, array $itemsForTax = []): array
    {
        $currency = $basket->getBasketCurrency();
        $currency = $currency ? clone $currency : (object)[];
        if (isset($currency->decimal)) {
            $currency->decimal = 2;
        }

        // Total order amount (2 decimals)
        $total = (float) number_format(
            $basket->getPrice()->getBruttoPrice(),
            2,
            '.',
            ''
        );

        // Breakdown components
        $shippingMoney = PriceToMoney::convert($basket->getPayPalCheckoutDeliveryCosts(), $currency);
        $discountMoney = PriceToMoney::convert($basket->getPayPalCheckoutDiscountBrutto(), $currency);
        // tax_total derived from items (per-unit tax * quantity), like OrderRequestFactory::autoFillTaxTotalFromItems
        $taxTotalFloat = $this->sumTaxFromItems($itemsForTax);
        $taxTotalMoney = PriceToMoney::convert($taxTotalFloat, $currency);

        // item_total equals sum(unit_price * quantity) where unit_price respects shop price mode
        $itemTotal = 0.0;
        foreach ((array) $basket->getContents() as $basketItem) {
            $unitPrice = $basketItem->getUnitPrice();
            if ($unitPrice) {
                $qty = (float) $basketItem->getAmount();
                $val = (float) $unitPrice->getPrice();
                $itemTotal += $qty * $val;
            }
        }
        $itemTotalMoney = PriceToMoney::convert($itemTotal, $currency);

        return [
            'value' => $total,
            'currency_code' => $currency->name ?? '',
            'breakdown' => [
                'shipping' => [
                    'currency_code' => $shippingMoney->currency_code,
                    'value' => $shippingMoney->value,
                ],
                'discount' => [
                    'currency_code' => $discountMoney->currency_code,
                    'value' => $discountMoney->value,
                ],
                'tax_total' => [
                    'currency_code' => $taxTotalMoney->currency_code,
                    'value' => $taxTotalMoney->value,
                ],
                'item_total' => [
                    'currency_code' => $itemTotalMoney->currency_code,
                    'value' => $itemTotalMoney->value,
                ],
            ],
        ];
    }

    /**
     * Map basket contents to PayPal items array.
     */
    private function mapItems(Basket $basket): array
    {
        $items = [];
        $currency = $basket->getBasketCurrency();
        if ($currency) {
            $currency->decimal = 2;
        }

        $isNetMode = (bool)Registry::getConfig()->getConfigParam('blShowNetPrice');

        /** @var BasketItem $basketItem */
        foreach ($basket->getContents() as $basketItem) {
            if (!($basketItem instanceof BasketItem)) {
                continue;
            }
            $title = (string)$basketItem->getTitle();
            $qty = (string)$basketItem->getAmount();
            $sku = (string)$basketItem->getArticle()->getFieldData('oxartnum');
            $unitPrice = $basketItem->getUnitPrice();
            if (!$unitPrice) {
                continue;
            }

            // Use NET or GROSS depending on shop setting
            $value = $isNetMode ? (float)$unitPrice->getNettoPrice() : (float)$unitPrice->getBruttoPrice();
            if ($value <= 0) {
                continue; // skip zero-price entries
            }

            $unitAmount = PriceToMoney::convert($value, $currency);

            // Determine per-unit tax value depending on pricing mode
            $vatPercent = (float)($unitPrice->getVat() ?? 0.0);
            $taxValueStr = '0.00';
            if ($isNetMode) {
                // In net mode, PayPal expects per-unit tax amount
                $taxPerUnit = $value * ($vatPercent / 100);
                $taxMoney = PriceToMoney::convert($taxPerUnit, $currency);
                $taxValueStr = $taxMoney->value;
            }

            $items[] = [
                'name' => $title,
                'sku' => $sku,
                'quantity' => $qty,
                'unit_amount' => [
                    'currency_code' => $unitAmount->currency_code,
                    'value' => $unitAmount->value,
                ],
                'tax' => [ 'currency_code' => $unitAmount->currency_code, 'value' => $taxValueStr ],
                'tax_rate' => (string)($unitPrice->getVat() ?? '0'),
                'category' => $this->inferItemCategory($basketItem),
            ];
        }

        // Additional lines (wrapping, gift card, payment, surcharge, rounding diff) can be added here if needed
        return $items;
    }

    private function inferItemCategory(BasketItem $basketItem): string
    {
        try {
            $article = $basketItem->getArticle();
            if (
                $article && method_exists($article, 'isVirtualPayPalArticle')
                && $article->isVirtualPayPalArticle()
            ) {
                return ApiItem::CATEGORY_DIGITAL_GOODS;
            }
        } catch (Throwable $e) {
            // ignore and fallback below
        }
        return ApiItem::CATEGORY_PHYSICAL_GOODS;
    }

    /**
     * Ported from OrderRequestFactory::autoFillTaxTotalFromItems logic.
     * Sum per-item tax (per-unit tax * quantity) from items array.
     */
    private function sumTaxFromItems(array $items): float
    {
        if (empty($items)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $qty = isset($it['quantity']) ? (float)$it['quantity'] : 1.0;
            $taxPerUnit = 0.0;
            if (isset($it['tax']['value']) && is_array($it['tax'])) {
                $taxPerUnit = (float)$it['tax']['value'];
            }
            $sum += $qty * $taxPerUnit;
        }
        // round to two decimals to mimic money rounding
        return round($sum, 2);
    }

    private function mapAmountWithBreakdown(array $amountArr): AmountWithBreakdown
    {
        $amount = new AmountWithBreakdown();
        $amount->currency_code = (string)($amountArr['currency_code'] ?? 'USD');
        $amount->value = $this->toMoneyValue((float)($amountArr['value'] ?? 0.0));

        if (!empty($amountArr['breakdown']) && is_array($amountArr['breakdown'])) {
            $amount->breakdown = $this->mapAmountBreakdown($amountArr['breakdown'], $amount->currency_code);
        }
        return $amount;
    }

    /**
     * Use PayPalAmountValidator to adjust breakdown to resolve rounding issues.
     * If items array is empty, validator will no-op and return original data.
     */
    private function applyAmountValidator(array $itemsArr, array $amountArr): array
    {
        try {
            $validator = Registry::get(PayPalAmountValidator::class);
        } catch (Throwable $e) {
            // If validator class cannot be instantiated for any reason, return original
            return $amountArr;
        }

        $orderData = [
            'items' => $itemsArr,
            'breakdown' => $amountArr['breakdown'] ?? [],
            'amount_value' => $amountArr['value'] ?? 0.0,
        ];

        try {
            $adjusted = $validator->validateAndAdjustOrder($orderData);
            if (isset($adjusted['breakdown']) && is_array($adjusted['breakdown'])) {
                $amountArr['breakdown'] = $adjusted['breakdown'];
            }
        } catch (Throwable $e) {
            // Fallback silently in case of any unexpected error
        }

        return [
            "items" => $adjusted['items'] ?? [],
            "amount" => $amountArr
        ];
    }

    private function mapAmountBreakdown(array $bdArr, string $currency): AmountBreakdown
    {
        $bd = new AmountBreakdown();
        // Known components per PayPal docs
        foreach (['item_total','shipping','tax_total','handling','insurance','shipping_discount','discount'] as $key) {
            if (isset($bdArr[$key]) && is_array($bdArr[$key])) {
                $money = new stdClass();
                $money->currency_code = (string)($bdArr[$key]['currency_code'] ?? $currency);
                $money->value = $this->toMoneyValue((float)($bdArr[$key]['value'] ?? 0.0));
                $bd->{$key} = $money;
            }
        }
        return $bd;
    }

    private function round2(float $v): float
    {
        return round($v, self::DECIMALS);
    }

    private function toMoneyValue(float $v): string
    {
        return number_format($this->round2($v), self::DECIMALS, '.', '');
    }

    public function setBasket(Basket $basket)
    {
        $this->basket = $basket;
    }
}
