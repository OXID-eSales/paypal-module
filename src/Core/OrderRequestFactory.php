<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use DateTime;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable3;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Payer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PhoneWithType;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnit;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ShippingDetail;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;
use OxidSolutionCatalysts\PayPalApi\Pui\PuiPaymentSource;

/**
 * Class OrderRequestBuilder
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class OrderRequestFactory
{
    /**
     * After you redirect the customer to the PayPal payment page, a Continue button appears.
     * Use this option when the final amount is not known when the checkout flow is initiated and you want to
     * redirect the customer to the merchant page without processing the payment.
     */
    public const USER_ACTION_CONTINUE = 'CONTINUE';

    public const USER_ACTION_PAY_NOW = 'PAY_NOW';

    /**
     * @var OrderRequest
     */
    private $request;

    /**
     * @var Basket
     */
    private $basket;

    /**
     * @param Basket $basket
     * @param string $intent Order::INTENT_CAPTURE or Order::INTENT_AUTHORIZE constant values
     * @param string $userAction USER_ACTION_CONTINUE constant values
     * @param null|string $transactionId transaction id
     * @param null|string $invoiceId custom invoice number
     * @param null|string $processingInstruction processing instruction
     *
     * @return OrderRequest
     */
    public function getRequest(
        Basket $basket,
        string $intent,
        ?string $userAction = null,
        ?string $transactionId = null,
        ?string $processingInstruction = null,
        ?string $paymentSource = null,
        ?string $invoiceId = null,
        ?string $returnUrl = null,
        ?string $cancelUrl = null,
        bool $withArticles = true
    ): OrderRequest {
        $request = $this->request = new OrderRequest();
        $this->basket = $basket;

        $request->intent = $intent;
        if (!$paymentSource && $basket->getUser()) {
            $request->payer = $this->getPayer();
        }

        $request->purchase_units = $this->getPurchaseUnits($transactionId, $invoiceId, $withArticles);

        if ($userAction || $returnUrl || $cancelUrl) {
            $request->application_context = $this->getApplicationContext($userAction, $returnUrl, $cancelUrl);
        }

        if ($processingInstruction) {
            $request->processing_instruction = $processingInstruction;
        }

        if ($paymentSource) {
            $request->payment_source =
            [
                $paymentSource => $this->getPaymentSource()
            ];
        }

        return $request;
    }

    /**
     * Sets application context
     *
     * @param string $userAction
     *
     * @return OrderApplicationContext
     */
    protected function getApplicationContext(
        ?string $userAction,
        ?string $returnUrl,
        ?string $cancelUrl
    ): OrderApplicationContext {
        $context = new OrderApplicationContext();
        $context->brand_name = Registry::getConfig()->getActiveShop()->getFieldData('oxname');
        $context->shipping_preference = 'GET_FROM_FILE';
        $context->landing_page = 'LOGIN';
        if ($userAction) {
            $context->user_action = $userAction;
        }
        if ($returnUrl) {
            $context->return_url = $returnUrl;
        }
        if ($cancelUrl) {
            $context->cancel_url = $cancelUrl;
        }

        return $context;
    }

    /**
     * @return PurchaseUnitRequest[]
     */
    protected function getPurchaseUnits(
        ?string $transactionId,
        ?string $invoiceId,
        bool $withArticles = true
    ): array {
        $purchaseUnit = new PurchaseUnitRequest();
        $shopName = Registry::getConfig()->getActiveShop()->getFieldData('oxname');
        $lang = Registry::getLang();

        $purchaseUnit->custom_id = $transactionId;
        $purchaseUnit->invoice_id = $invoiceId;
        $description = sprintf($lang->translateString('OSC_PAYPAL_DESCRIPTION'), $shopName);
        $purchaseUnit->description = $description;

        $purchaseUnit->amount = $this->getAmount();
        $purchaseUnit->reference_id = Constants::PAYPAL_ORDER_REFERENCE_ID;

        // If it is planned to patch this PayPal order in the further course,
        // then no items may be given, since PayPal cannot patch any items at the moment
        // At the moment only the amount and the title of the article
        // are relevant. However, no inventory.
        // in this case get the purchase units without articles
        if ($withArticles) {
            $purchaseUnit->items = $this->getItems();
        }

        if ($this->basket->getBasketUser()) {
            $purchaseUnit->shipping = $this->getShippingAddress();
        }

        return [$purchaseUnit];
    }

    /**
     * @return AmountWithBreakdown
     */
    protected function getAmount(): AmountWithBreakdown
    {
        $amount = new AmountWithBreakdown();
        $basket = $this->basket;
        $currency = $this->basket->getBasketCurrency();

        $total = PriceToMoney::convert($this->basket->getPrice(), $currency);

        //Total amount
        $amount->value = $total->value;
        $amount->currency_code = $total->currency_code;

        //Cost breakdown
        $breakdown = $amount->breakdown = new AmountBreakdown();

        //Item total cost
        $itemTotal = $basket->getPayPalProductNetto();
        $breakdown->item_total = PriceToMoney::convert((float)$itemTotal, $currency);

        //Item tax sum
        $tax = $basket->getPayPalProductVatValue();
        $breakdown->tax_total = PriceToMoney::convert((float)$tax, $currency);

        if ($basket->getDeliveryCost()) {
            //Shipping cost
            $shippingCost = $basket->getDeliveryCost();
            $breakdown->shipping = PriceToMoney::convert($shippingCost, $currency);
        }

        if ($discount = $basket->getDiscountSumPayPalBasket()) {
            //Discount
            $breakdown->discount = PriceToMoney::convert((float)$discount, $currency);
        }

        return $amount;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        $basket = $this->basket;
        $currency = $basket->getBasketCurrency();
        $language = Registry::getLang();
        $items = [];

        /** @var BasketItem $basketItem */
        foreach ($basket->getContents() as $basketItem) {
            $item = new Item();
            $item->name = $basketItem->getTitle();
            $itemUnitPrice = $basketItem->getUnitPrice();

            $item->unit_amount = PriceToMoney::convert($itemUnitPrice->getNettoPrice(), $currency);
            $item->tax = PriceToMoney::convert($itemUnitPrice->getVatValue(), $currency);
            $item->tax_rate = (string)$itemUnitPrice->getVat();
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = 'PHYSICAL_GOODS';

            $item->quantity = (string)$basketItem->getAmount();
            $items[] = $item;
        }

        if ($wrapping = $basket->getPayPalCheckoutWrappingCosts()) {
            $item = new Item();
            $item->name = $language->translateString('GIFT_WRAPPING');

            $item->unit_amount = PriceToMoney::convert((float)$wrapping, $currency);
            $item->tax = PriceToMoney::convert((float)$basket->getPayPalCheckoutWrappingVatValue(), $currency);
            $item->tax_rate = $basket->getPayPalCheckoutWrappingVat();
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = 'PHYSICAL_GOODS';

            $item->quantity = '1';
            $items[] = $item;
        }

        if ($giftCard = $basket->getPayPalCheckoutGiftCardCosts()) {
            $item = new Item();
            $item->name = $language->translateString('GREETING_CARD');

            $item->unit_amount = PriceToMoney::convert((float)$giftCard, $currency);
            $item->tax = PriceToMoney::convert((float)$basket->getPayPalCheckoutGiftCardVatValue(), $currency);
            $item->tax_rate = $basket->getPayPalCheckoutGiftCardVat();
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = 'PHYSICAL_GOODS';

            $item->quantity = '1';
            $items[] = $item;
        }

        if (($payment = $basket->getPayPalCheckoutPaymentCosts()) > 0) {
            $item = new Item();
            $item->name = $language->translateString('PAYMENT_METHOD');

            $item->unit_amount = PriceToMoney::convert((float)$payment, $currency);
            $item->tax = PriceToMoney::convert((float)$basket->getPayPalCheckoutPaymentVatValue(), $currency);
            $item->tax_rate = $basket->getPayPalCheckoutPaymentVat();
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = 'PHYSICAL_GOODS';

            $item->quantity = '1';
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return Payer
     */
    protected function getPayer(string $payerClass = Payer::class): Payer
    {
        $payer = new $payerClass();
        $user = $this->basket->getBasketUser();

        $name = $payer->initName();
        $name->given_name = $user->getFieldData('oxfname');
        $name->surname = $user->getFieldData('oxlname');

        $payer->email_address = $user->getFieldData('oxusername');
        $payer->phone = $this->getPayerPhone();

        $birthDate = $user->getFieldData('oxbirthdate');
        if ($birthDate && $birthDate !== '0000-00-00') {
            /** @var DateTime $birthDate */
            $birthDate = oxNew(DateTime::class, $user->getFieldData('oxbirthdate'));
            $payer->birth_date = $birthDate->format('Y-m-d');
        }

        $payer->address = $this->getBillingAddress();

        return $payer;
    }

    /**
     * @return AddressPortable3
     */
    protected function getBillingAddress(): AddressPortable3
    {
        $user = $this->basket->getBasketUser();

        $state = oxNew(State::class);
        $state->load($user->getFieldData('oxstateid'));

        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        $address = new AddressPortable3();
        $addressLine = $user->getFieldData('oxstreet') . " " . $user->getFieldData('oxstreetnr');
        $address->address_line_1 = $addressLine;
        $address->admin_area_1 = $state->getFieldData('oxtitle');
        $address->admin_area_2 = $user->getFieldData('oxcity');
        $address->country_code = $country->oxcountry__oxisoalpha2->value;
        $address->postal_code = $user->getFieldData('oxzip');

        return $address;
    }

    /**
     * @return ShippingDetail|null
     */
    protected function getShippingAddress(): ?ShippingDetail
    {
        $user = $this->basket->getBasketUser();
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);
        $shipping = new ShippingDetail();
        $name = $shipping->initName();
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $fullName = $deliveryAddress->oxaddress__oxfname->value . " " . $deliveryAddress->oxaddress__oxlname->value;
            $name->full_name = $fullName;

            $address = new AddressPortable3();

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

            $shipping->address = $address;
        } else {
            $fullName = $user->getFieldData('oxfname') . " " . $user->getFieldData('oxlname');
            $name->full_name = $fullName;

            $shipping->address = $this->getBillingAddress();
        }

        return $shipping;
    }

    /**
     * @return PhoneWithType|null
     */
    protected function getPayerPhone(): ?PhoneWithType
    {
        $user = $this->basket->getBasketUser();
        $phoneUtils = PhoneNumberUtil::getInstance();

        //Array of phone numbers to use in the request, using the first from the sequence that is available and valid.
        $userPhoneFields = [
            'oxmobfon' => 'MOBILE',
            'oxprivfon' => 'MOBILE',
            'oxfon' => 'HOME',
            'oxfax' => 'FAX'
        ];

        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));
        $countryCode = $country->oxcountry__oxisoalpha2->value;

        $number = null;

        foreach ($userPhoneFields as $numberField => $numberType) {
            $number = $user->getFieldData($numberField);

            if (!$number) {
                continue;
            }

            try {
                $phoneNumber = $phoneUtils->parse($number, $countryCode);
                if ($phoneUtils->isValidNumber($phoneNumber)) {
                    $number = ltrim($phoneUtils->format($phoneNumber, PhoneNumberFormat::E164), '+');
                    $type = $numberType;
                    break;
                }
            } catch (NumberParseException $exception) {
            }
        }

        if (!$number) {
            return null;
        }

        $phone = new PhoneWithType();
        $phone->phone_type = $type;
        $phone->phone_number->national_number = $number;

        return $phone;
    }

    protected function getPaymentSource(): PuiPaymentSource
    {
        $user = $this->basket->getBasketUser();

        // get Billing CountryCode
        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        // check possible deliveryCountry
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $country->load($deliveryAddress->getFieldData('oxcountryid'));
        }

        $payer = $this->getPayer();

        $billingAddress = new AddressPortable();
        $billingAddress->address_line_1 = $payer->address->address_line_1;
        $billingAddress->admin_area_2 = $payer->address->admin_area_2;
        $billingAddress->postal_code = $payer->address->postal_code;
        $billingAddress->country_code = $payer->address->country_code;

        $paymentSource = new PuiPaymentSource();
        $paymentSource->name = $payer->name;
        $paymentSource->email = $payer->email_address;
        $paymentSource->billing_address = $billingAddress;

        $paymentSource->phone = $user->getPhoneNumberForPuiRequest();
        if ($birthdate = $user->getBirthDateForPuiRequest()) {
            $paymentSource->birth_date = $birthdate;
        }

        $activeShop = Registry::getConfig()->getActiveShop();
        $experienceContext = new ExperienceContext();
        $experienceContext->brand_name = $activeShop->getFieldData('oxname');
        $experienceContext->locale = strtolower($payer->address->country_code)
            . '-'
            .  strtoupper($payer->address->country_code);
        $experienceContext->customer_service_instructions[] = $activeShop->getFieldData('oxinfoemail');
        $paymentSource->experience_context = $experienceContext;

        return $paymentSource;
    }
}
