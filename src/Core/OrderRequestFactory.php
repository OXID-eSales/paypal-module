<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use DateTime;
use JsonException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Helper\Truncate;
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable3;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Item;
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
    use CustomerAddressHelper;

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
        bool $setProvidedAddress = true
    ): OrderRequest {
        $request = $this->request = new OrderRequest();
        $this->basket = $basket;
        $withItems = !$this->basket->isCalculationModeNetto();

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $setVaulting = $moduleSettings->getIsVaultingActive();

        $paymentId = Registry::getSession()->getVariable('paymentid');
        $paymentSourceId = PayPalDefinitions::getPaymentSourceRequestName($paymentId);

        $request->payment_source = $this->getSimplePaymentSource($basket, $paymentSourceId);
        if ($paymentId === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID) {
            $request->payment_source = $this->getGooglePayPaymentSource($basket, $paymentSourceId);
        }

        if ($paymentId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID) {
            $request->payment_source = $this->getSimplePaymentSource($basket, $paymentSourceId);
        }

        if (PayPalDefinitions::isUAPMPayment($paymentId)) {
            if ($paymentId === PayPalDefinitions::PRZELEWY24_PAYPAL_PAYMENT_ID) {
                $request->payment_source = $this->getSimplePaymentSourceWithEMail($basket, $paymentSourceId);
            }
            else {
                $request->payment_source = $this->getSimplePaymentSource($basket, $paymentSourceId);
            }
        }

        $request->intent = $intent;
        $request->purchase_units = $this->getPurchaseUnits($customId, $invoiceId, $withItems);
        $vaultingService = $this->getVaultingService();
        $user = Registry::getConfig()->getUser() instanceof User ? Registry::getConfig()->getUser() : null;
        $selectedPaymentToken = $vaultingService->fetchSelectedVaultedPaymentToken(
            $user, $_POST["useVaultedPayment"]["token"]["id"] ?? null
        );
        $useVaultedPayment = $setVaulting && !is_null($selectedPaymentToken)
            && PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID !== $paymentId;

        if ($useVaultedPayment) {

            $paymentSourceId = PayPalDefinitions::getPaymentSourceRequestName(
                key($selectedPaymentToken["payment_source"])
            );

            $this->modifyPaymentSourceForVaulting($request, $paymentSourceId, $returnUrl, $cancelUrl);
            return $request;
        }

        if (Registry::getRequest()->getRequestParameter("vaultPayment") === "true") {
            $paymentSourceId = PayPalDefinitions::getPaymentSourceRequestName(
                Registry::getRequest()->getRequestParameter("oscPayPalPaymentTypeForVaulting")
            );
            Registry::getSession()->setVariable("vaultSuccess", true);
            $this->modifyPaymentSourceForVaulting($request, $paymentSourceId, $returnUrl, $cancelUrl);

            return $request;
        } else {
            $this->modifyPaymentSourceForVaulting($request, $paymentSourceId, $returnUrl, $cancelUrl);
        }

        if (!$paymentSource && $basket->getUser()) {
            $request->payer = $this->getPayer();
        }

        if ($processingInstruction) {
            $request->processing_instruction = $processingInstruction;
        }

        if ($paymentSource === PayPalDefinitions::PAYMENT_SOURCE_PUI) {
            /** @var PaymentSource $puiPaymentSource */
            $puiPaymentSource = $this->getPuiPaymentSource();
            $request->payment_source = $puiPaymentSource;
        }

        return $request;
    }

    protected function getSimplePaymentSource(Basket $basket, string $requestName): PaymentSource
    {
        $userName = $this->getUserNameFromBasket($basket);
        $country = $this->getCountryFromBasket($basket);

        return new PaymentSource([
            $requestName => [
                'name' => $userName,
                'country_code' => $country->getFieldData('oxisoalpha2')
            ]
        ]);
    }

    protected function getSimplePaymentSourceWithEMail(Basket $basket, string $requestName): PaymentSource
    {
        $userName = $this->getUserNameFromBasket($basket);
        $country = $this->getCountryFromBasket($basket);
        $eMail = $this->getEMailFromBasket($basket);

        return new PaymentSource([
            $requestName => [
                'name' => $userName,
                'email' => $eMail,
                'country_code' => $country->getFieldData('oxisoalpha2')
            ]
        ]);
    }

    /**
     * @return PurchaseUnitRequest[]
     */
    protected function getPurchaseUnits(
        ?string $transactionId,
        ?string $invoiceId,
        bool $withItems = false
    ): array {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $purchaseUnit = new PurchaseUnitRequest();
        $shopName = $moduleSettings->getShopName();
        $lang = Registry::getLang();

        $purchaseUnit->custom_id = $transactionId;
        $purchaseUnit->invoice_id = $invoiceId;
        $description = sprintf($lang->translateString('OSC_PAYPAL_DESCRIPTION'), $shopName);
        $purchaseUnit->description = $description;

        $purchaseUnit->amount = $this->getAmount();
        $purchaseUnit->reference_id = Constants::PAYPAL_ORDER_REFERENCE_ID;

        if ($withItems) {
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
        return (Registry::get(PayPalRequestAmountFactory::class))->getAmount($this->basket);
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
        //only two decimal place precision is supported in PayPal
        $currency->decimal = 2;
        $language = Registry::getLang();
        $items = [];

        /** @var BasketItem $basketItem */
        foreach ($basket->getContents() as $basketItem) {
            $item = new Item();
            $item->name = (new Truncate())->truncate($basketItem->getTitle());
            $itemUnitPrice = $basketItem->getUnitPrice();

            $basketArticle = $basketItem->getArticle();
            $articleCategory = ($basketArticle->isVirtualPayPalArticle())
                ? Item::CATEGORY_DIGITAL_GOODS
                : Item::CATEGORY_PHYSICAL_GOODS;

            // no zero price articles in the list
            if ($itemUnitPrice && $itemUnitPrice->getBruttoPrice() > 0) {
                $item->unit_amount = PriceToMoney::convert(
                    $itemUnitPrice->getBruttoPrice(),
                    $currency
                );
                // tax - we use 0% and calculate with brutto to avoid rounding errors
                $item->tax = PriceToMoney::convert(0.0, $currency);
                $item->tax_rate = '0';
                $item->category = $articleCategory;

                $item->quantity = (string)$basketItem->getAmount();
                $items[] = $item;
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
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0.0, $currency);
            $item->tax_rate = '0';
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        $giftCard = $basket->getPayPalCheckoutGiftCard();
        if ($giftCard) {
            $item = new Item();
            $item->name = $language->translateString('GREETING_CARD');

            $item->unit_amount = PriceToMoney::convert(
                $giftCard,
                $currency
            );
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0.0, $currency);
            $item->tax_rate = '0';
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        $payment = $basket->getPayPalCheckoutPayment();
        if ($payment) {
            $item = new Item();
            $item->name = $language->translateString('PAYMENT_METHOD');

            $item->unit_amount = PriceToMoney::convert(
                $payment,
                $currency
            );
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0.0, $currency);
            $item->tax_rate = '0';
            $item->category = $itemCategory;

            $item->quantity = '1';
            $items[] = $item;
        }

        // possible price surcharge
        $discount = $basket->getPayPalCheckoutDiscount();

        if ($discount < 0) {
            $discount *= -1;
            $item = new Item();
            $item->name = $language->translateString('SURCHARGE');

            $item->unit_amount = PriceToMoney::convert($discount, $currency);
            // tax - we use 0% and calculate with brutto to avoid rounding errors
            $item->tax = PriceToMoney::convert(0.0, $currency);
            $item->tax_rate = '0';
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
            $item->tax = PriceToMoney::convert(0.0, $currency);
            $item->tax_rate = '0';
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

        $type = null;
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

        if (!$number || !$type) {
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
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

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

        $experienceContext = new ExperienceContext();
        $experienceContext->brand_name = $moduleSettings->getShopName();
        $experienceContext->locale = strtolower($payer->address->country_code)
            . '-'
            .  strtoupper($payer->address->country_code);
        $experienceContext->customer_service_instructions[] = $moduleSettings->getInfoEMail();
        $paymentSource->experience_context = $experienceContext;

        return [PayPalDefinitions::PAYMENT_SOURCE_PUI => $paymentSource];
    }

    /**
     * @param OrderRequest $request
     * @param string $paymentSourceId
     * @param string|null $returnUrl
     * @param string|null $cancelUrl
     * @return void
     */
    protected function modifyPaymentSourceForVaulting(
        OrderRequest $request,
        string $paymentSourceId,
        ?string $returnUrl = null,
        ?string $cancelUrl = null
    ): void {
        $config = Registry::getConfig();
        $vaultingService = $this->getVaultingService();

        $selectedVaultPaymentSourceIndex = Registry::getSession()->getVariable("selectedVaultPaymentSourceIndex");

        //use selected vault
        if (!is_null($selectedVaultPaymentSourceIndex) && $payPalCustomerId = $this->getUsersPayPalCustomerId()) {
            $paymentTokens = $vaultingService->getVaultPaymentTokens($payPalCustomerId);
            //find out which payment token was selected by getting the index via request param
            $selectedPaymentToken = $paymentTokens["payment_tokens"][$selectedVaultPaymentSourceIndex];

            $newPaymentSource = [
                $paymentSourceId => [
                    "vault_id" => $selectedPaymentToken["id"],
                    "attributes" => [
                        "verification" => [
                            "method" => "SCA_WHEN_REQUIRED",
                            "_comment" => "SCA_ALWAYS to force otherwise use SCA_WHEN_REQUIRED"
                        ],
                        "customer" => [
                            "id" => $payPalCustomerId
                        ]
                    ],
                    "stored_credential" => [
                        "payment_initiator" => "CUSTOMER",
                        "payment_type" => "UNSCHEDULED",
                        "usage" => "SUBSEQUENT"
                    ],
                    "experience_context" => [
                        "return_url" => $returnUrl,
                        "cancel_url" => $cancelUrl
                    ]
                ]
            ];
            $request->payment_source = $newPaymentSource;
        } elseif ($user = $config->getUser()) {
            //save during purchase
            $paypalCustomerId = $user->getFieldData("oscpaypalcustomerid");

            if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_CARD) {
                $newPaymentSource = $vaultingService->getPaymentSourceForVaulting($paymentSourceId);
                $newPaymentSource["attributes"] = [
                    "verification" => [
                        "method" => "SCA_WHEN_REQUIRED"
                    ],
                    "vault" => [
                        "store_in_vault" => "ON_SUCCESS"
                    ],
                ];

                $newPaymentSource[$paymentSourceId]["attributes"]["customer"] = [
                    "id" => $paypalCustomerId
                ];
            } else {
                $newPaymentSource = [
                    $paymentSourceId => [
                        "experience_context" => [
                            "return_url" => $config->getSslShopUrl() .
                                'index.php?cl=order&fnc=finalizepaypalsession',
                            "cancel_url" => $config->getSslShopUrl() .
                                'index.php?cl=order&fnc=cancelpaypalsession',
                            "shipping_preference" => "SET_PROVIDED_ADDRESS",
                        ]
                    ]
                ];
                // it is possible that we have an existing payment_source. We must merge it
                if (isset($request->payment_source->{$paymentSourceId})) {
                    $paymentSourceData = $this->getArrayFromPaymentSource($request, $paymentSourceId);
                    $newPaymentSource[$paymentSourceId] = array_merge(
                        $paymentSourceData,
                        $newPaymentSource[$paymentSourceId]
                    );
                }

                if (
                    in_array($paymentSourceId, PayPalDefinitions::VAULTABLE_PAYMENT_SOURCES)
                ) {
                    $vaultSuccess = Registry::getSession()->getVariable("vaultSuccess");
                    if (
                        $vaultSuccess &&
                        !isset($request->payment_source->{$paymentSourceId}->attributes->vault)
                    ) {
                        $newPaymentSource[$paymentSourceId]["attributes"]["vault"]
                            = PayPalDefinitions::PAYMENT_VAULTING;
                    }

                    if (
                        $paypalCustomerId &&
                        !isset($request->payment_source->{$paymentSourceId}->attributes->customer->id)
                    ) {
                        $newPaymentSource[$paymentSourceId]["attributes"]["customer"]["id"] = $paypalCustomerId;
                    }
                }
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
        $user = Registry::getConfig()->getUser();
        return $user ? $user->getFieldData("oscpaypalcustomerid") : '';
    }

    /**
     * @throws JsonException
     */
    private function getArrayFromPaymentSource(OrderRequest $request, string $paymentSourceId): array
    {
        $encodedData = json_encode($request->payment_source->{$paymentSourceId}, JSON_THROW_ON_ERROR);
        return json_decode($encodedData, true, 512, JSON_THROW_ON_ERROR);
    }
}
