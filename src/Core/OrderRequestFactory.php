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
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Model\Article;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable3;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Payer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PhoneWithType;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ShippingDetail;
use OxidSolutionCatalysts\PayPal\Core\Utils\PriceToMoney;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;
use OxidSolutionCatalysts\PayPalApi\Pui\PuiPaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * Class OrderRequestBuilder
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class OrderRequestFactory
{
    use ServiceContainer;

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
     * @param null|string $userAction USER_ACTION_CONTINUE constant values
     * @param null|string $customId custom id reference
     * @param null|string $processingInstruction processing instruction
     * @param null|string $paymentSource Payment-Source Name
     * @param null|string $invoiceId custom invoice number
     * @param null|string $returnUrl Return Url
     * @param null|string $cancelUrl Cancel Url
     * @param bool $withArticles Request with article information?
     * @param bool $setProvidedAddress Address changeable in PayPal?
     *
     * @return OrderRequest
     */
    public function getRequest(
        Basket $basket,
        string $intent,
        ?string $userAction = null,
        ?string $customId = null,
        ?string $processingInstruction = null,
        ?string $paymentSource = null,
        ?string $invoiceId = null,
        ?string $returnUrl = null,
        ?string $cancelUrl = null,
        bool $withArticles = true,
        bool $setProvidedAddress = true
    ): OrderRequest {
        $request = $this->request = new OrderRequest();
        $this->basket = $basket;

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $setVaulting = $moduleSettings->getIsVaultingActive();
        $selectedVaultPaymentSourceIndex = Registry::getSession()->getVariable("selectedVaultPaymentSourceIndex");

        $request->intent = $intent;
        $request->purchase_units = $this->getPurchaseUnits($transactionId, $invoiceId, $withArticles);

        $useVaultedPayment = $setVaulting && !is_null($selectedVaultPaymentSourceIndex);
        if ($useVaultedPayment) {
            $config = Registry::getConfig();
            $vaultingService = $this->getVaultingService();
            $payPalCustomerId = $this->getUsersPayPalCustomerId();

            $selectedPaymentToken = $vaultingService->getVaultPaymentTokenByIndex(
                $payPalCustomerId,
                $selectedVaultPaymentSourceIndex
            );
            //find out which payment token was selected by getting the index via request param
            $paymentType = key($selectedPaymentToken["payment_source"]);
            $useCard = $paymentType == "card";

            $this->modifyPaymentSourceForVaulting($request, $useCard);

            //we use the PayPal payment type as a "dummy payment" when we use vaulted payments.
            //therefore, we need to use a returnURL depending on the payment type.
            if ($useCard) {
                $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizeacdc';
            }

            $request->application_context = $this->getApplicationContext(
                "",
                $returnUrl,
                $cancelUrl,
                false
            );
            return $request;
        } elseif (Registry::getRequest()->getRequestParameter("vaultPayment")) {
            $paymentType = Registry::getRequest()->getRequestParameter("oscPayPalPaymentTypeForVaulting");
            $card = $paymentType == PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;

            $this->modifyPaymentSourceForVaulting($request, $card);

            return $request;
        }

        if (!$paymentSource && $basket->getUser()) {
            $request->payer = $this->getPayer();
        }

        if ($userAction || $returnUrl || $cancelUrl) {
            $request->application_context = $this->getApplicationContext(
                $userAction,
                $returnUrl,
                $cancelUrl,
                $setProvidedAddress
            );
        }

        if ($processingInstruction) {
            $request->processing_instruction = $processingInstruction;
        }

        if ($paymentSource == PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME) {
            /** @var PaymentSource $puiPaymentSource */
            $puiPaymentSource = $this->getPuiPaymentSource();
            $request->payment_source = $puiPaymentSource;
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
        ?string $cancelUrl,
        ?bool $setProvidedAddress
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
        if ($setProvidedAddress) {
            $context->shipping_preference = "SET_PROVIDED_ADDRESS";
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
        $basket = $this->basket;
        $currency = $basket->getBasketCurrency();

        //Discount
        $discount = $basket->getPayPalCheckoutDiscount();
        //Item total cost
        $itemTotal = $basket->getPayPalCheckoutItems();

        // possible price surcharge
        if ($discount < 0) {
            $itemTotal -= $discount;
            $discount = 0;
        }
        $total = $itemTotal - $discount;

        $total = PriceToMoney::convert($total, $currency);

        //Total amount
        $amount = new AmountWithBreakdown();
        $amount->value = $total->value;
        $amount->currency_code = $total->currency_code;

        //Cost breakdown
        $breakdown = $amount->breakdown = new AmountBreakdown();

        if ($discount) {
            $breakdown->discount = PriceToMoney::convert($discount, $currency);
        }

        $breakdown->item_total = PriceToMoney::convert($itemTotal, $currency);
        //Item tax sum - we use 0% and calculate with brutto to avoid rounding errors
        $breakdown->tax_total = PriceToMoney::convert(0, $currency);

        return $amount;
    }

    /**
     * @return array
     * @psalm-suppress UndefinedDocblockClass
     */
    public function getItems(): array
    {
        $basket = $this->basket;
        $itemCategory = $this->getItemCategoryByBasketContent();
        $currency = $basket->getBasketCurrency();
        $language = Registry::getLang();
        $items = [];

        /** @var BasketItem $basketItem */
        foreach ($basket->getContents() as $basketItem) {
            $item = new Item();
            $item->name = substr($basketItem->getTitle(), 0, 120);
            $itemUnitPrice = $basketItem->getUnitPrice();

            $basketArticle = $basketItem->getArticle();
            $articleCategory = ($basketArticle->isVirtualPayPalArticle())
                ? Item::CATEGORY_DIGITAL_GOODS
                : Item::CATEGORY_PHYSICAL_GOODS;

            // no zero price articles in the list
            if ((float)$itemUnitPrice->getBruttoPrice() > 0) {
                $item->unit_amount = PriceToMoney::convert((float)$itemUnitPrice->getBruttoPrice(), $currency);
                // tax - we use 0% and calculate with brutto to avoid rounding errors
                $item->tax = PriceToMoney::convert((float)0, $currency);
                $item->tax_rate = '0';
                // TODO: There are usually still categories for digital products.
                // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
                $item->category = $articleCategory;

                $item->quantity = (string)$basketItem->getAmount();
                $items[] = $item;
            }
        }

        if ($wrapping = $basket->getPayPalCheckoutWrapping()) {
            $item = new Item();
            $item->name = $language->translateString('GIFT_WRAPPING');

            $item->unit_amount = PriceToMoney::convert((float)$wrapping, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        if ($giftCard = $basket->getPayPalCheckoutGiftCard()) {
            $item = new Item();
            $item->name = $language->translateString('GREETING_CARD');

            $item->unit_amount = PriceToMoney::convert((float)$giftCard, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        if ($payment = $basket->getPayPalCheckoutPayment()) {
            $item = new Item();
            $item->name = $language->translateString('PAYMENT_METHOD');

            $item->unit_amount = PriceToMoney::convert((float)$payment, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        //Shipping cost
        if ($delivery = $basket->getPayPalCheckoutDeliveryCosts()) {
            $item = new Item();
            $item->name = $language->translateString('SHIPPING_COST');

            $item->unit_amount = PriceToMoney::convert((float)$delivery, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        $discount = $basket->getPayPalCheckoutDiscount();
        // possible price surcharge
        if ($discount < 0) {
            $discount *= -1;
            $item = new Item();
            $item->name = $language->translateString('SURCHARGE');

            $item->unit_amount = PriceToMoney::convert($discount, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';
            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        // Dummy-Article for Rounding-Error
        if ($roundDiff = $basket->getPayPalCheckoutRoundDiff()) {
            $item = new Item();
            $item->name = $language->translateString('OSC_PAYPAL_VAT_CORRECTION');

            $item->unit_amount = PriceToMoney::convert((float)$roundDiff, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0, $currency);
            $item->tax_rate = '0';

            // TODO: There are usually still categories for digital products.
            // But only with PHYSICAL_GOODS, Payments like PUI will work fine.
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Determine the item category based on the entire basket contents. If all items in the basket are virtual
     * the category "DIGITAL_GOODS" is used, in any other case it'll be "PHYSICAL_GOODS".
     * @return string
     */
    public function getItemCategoryByBasketContent(): string
    {
        return (
        $this->basket->isEntirelyVirtualPayPalBasket()
            ? Item::CATEGORY_DIGITAL_GOODS
            : Item::CATEGORY_PHYSICAL_GOODS
        );
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
        $state->loadByIdAndCountry(
            $user->getFieldData('oxstateid'),
            $user->getFieldData('oxcountryid')
        );

        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        $address = new AddressPortable3();
        $addressLine = $user->getFieldData('oxstreet') . " " . $user->getFieldData('oxstreetnr');
        $address->address_line_1 = $addressLine;
        $addinfoLine = $user->getFieldData('oxcompany') . " " . $user->getFieldData('oxaddinfo');
        $address->address_line_2 = $addinfoLine;
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
            $state->loadByIdAndCountry(
                $deliveryAddress->getFieldData('oxstateid'),
                $deliveryAddress->getFieldData('oxcountryid')
            );

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

    /**
     * @return PaymentSource[]
     */
    protected function getPuiPaymentSource(): array
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
        $billingAddress->address_line_2 = $payer->address->address_line_2;
        $billingAddress->admin_area_2 = $payer->address->admin_area_2;
        $billingAddress->postal_code = $payer->address->postal_code;
        $billingAddress->country_code = $payer->address->country_code;

        $paymentSource = new PuiPaymentSource();
        $paymentSource->name = $payer->name;
        $paymentSource->email = $payer->email_address;
        $paymentSource->billing_address = $billingAddress;

        /** @var ApiModelPhone $phoneNumberForPuiRequest */
        $phoneNumberForPuiRequest = $user->getPhoneNumberForPuiRequest();
        $paymentSource->phone = $phoneNumberForPuiRequest;
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

        return [PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME => $paymentSource];
    }

    /**
     * @param OrderRequest $request
     * @return void
     */
    protected function modifyPaymentSourceForVaulting(OrderRequest $request, $useCard = false): void
    {
        $config = Registry::getConfig();
        $vaultingService = $this->getVaultingService();

        $selectedVaultPaymentSourceIndex = Registry::getSession()->getVariable("selectedVaultPaymentSourceIndex");

        //use selected vault
        if (!is_null($selectedVaultPaymentSourceIndex) && $payPalCustomerId = $this->getUsersPayPalCustomerId()) {
            $paymentTokens = $vaultingService->getVaultPaymentTokens($payPalCustomerId);
            //find out which payment token was selected by getting the index via request param
            $selectedPaymentToken = $paymentTokens["payment_tokens"][$selectedVaultPaymentSourceIndex];

            $request->payment_source =
                [
                    "paypal" =>
                        [
                            "vault_id" => $selectedPaymentToken["id"],
                        ]
                ];
        } elseif ($config->getUser()) {
            //save during purchase
            if ($useCard) {
                $newPaymentSource = $vaultingService->getPaymentSourceForVaulting(true);
                $newPaymentSource["attributes"] = [
                    "verification" => [
                        "method" => "SCA_WHEN_REQUIRED"
                    ],
                    "vault" => [
                        "store_in_vault" => "ON_SUCCESS"
                    ],
                ];
            } else {
                $newPaymentSource = [
                    "paypal" =>
                        [
                            "attributes" =>
                                [
                                    "vault" =>
                                        PayPalDefinitions::PAYMENT_VAULTING
                                ],
                            "experience_context" =>
                                [
                                    "return_url" => $config->getSslShopUrl() .
                                        'index.php?cl=order&fnc=finalizepaypalsession',
                                    "cancel_url" => $config->getSslShopUrl() .
                                        'index.php?cl=order&fnc=cancelpaypalsession',
                                    "shipping_preference" => "SET_PROVIDED_ADDRESS",
                                ]
                        ],
                ];
            }

            $request->payment_source = $newPaymentSource;
        }
    }

    private function getVaultingService()
    {
        return Registry::get(ServiceFactory::class)->getVaultingService();
    }

    private function getUsersPayPalCustomerId()
    {
        $config = Registry::getConfig();
        return $config->getUser()->getFieldData("oscpaypalcustomerid");
    }
}
