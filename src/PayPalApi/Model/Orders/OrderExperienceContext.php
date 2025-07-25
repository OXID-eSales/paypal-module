<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Customizes the payer experience during the approval process for the payment with
 * PayPal.<blockquote><strong>Note:</strong> Partners and Marketplaces might configure <code>brand_name</code>
 * and <code>shipping_preference</code> during partner account setup, which overrides the request
 * values.</blockquote>
 *
 * generated from: order_application_context.json
 */
class OrderExperienceContext implements JsonSerializable
{
    use BaseModel;

    /** When the customer clicks <strong>PayPal Checkout</strong>, the customer is redirected to a page to log in to PayPal and approve the payment. */
    public const LANDING_PAGE_LOGIN = 'LOGIN';

    /** When the customer clicks <strong>PayPal Checkout</strong>, the customer is redirected to a page to enter credit or debit card and other relevant billing information required to complete the purchase. */
    public const LANDING_PAGE_BILLING = 'BILLING';

    /** When the customer clicks <strong>PayPal Checkout</strong>, the customer is redirected to either a page to log in to PayPal and approve the payment or to a page to enter credit or debit card and other relevant billing information required to complete the purchase, depending on their previous interaction with PayPal. */
    public const LANDING_PAGE_NO_PREFERENCE = 'NO_PREFERENCE';

    /** Use the customer-provided shipping address on the PayPal site. */
    public const SHIPPING_PREFERENCE_GET_FROM_FILE = 'GET_FROM_FILE';

    /** Redact the shipping address from the PayPal site. Recommended for digital goods. */
    public const SHIPPING_PREFERENCE_NO_SHIPPING = 'NO_SHIPPING';

    /** Use the merchant-provided address. The customer cannot change this address on the PayPal site. */
    public const SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS = 'SET_PROVIDED_ADDRESS';

    /** After you redirect the customer to the PayPal payment page, a <strong>Continue</strong> button appears. Use this option when the final amount is not known when the checkout flow is initiated and you want to redirect the customer to the merchant page without processing the payment. */
    public const USER_ACTION_CONTINUE = 'CONTINUE';

    /** After you redirect the customer to the PayPal payment page, a <strong>Pay Now</strong> button appears. Use this option when the final amount is known when the checkout is initiated and you want to process the payment immediately when the customer clicks <strong>Pay Now</strong>. */
    public const USER_ACTION_PAY_NOW = 'PAY_NOW';

    /**
     * The label that overrides the business name in the PayPal account on the PayPal site.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $brand_name;

    /**
     * The [language tag](https://tools.ietf.org/html/bcp47#section-2) for the language in which to localize the
     * error-related strings, such as messages, issues, and suggested actions. The tag is made up of the [ISO 639-2
     * language code](https://www.loc.gov/standards/iso639-2/php/code_list.php), the optional [ISO-15924 script
     * tag](https://www.unicode.org/iso15924/codelists.html), and the [ISO-3166 alpha-2 country
     * code](/docs/integration/direct/rest/country-codes/).
     *
     * @var string | null
     * minLength: 2
     * maxLength: 10
     */
    public $locale;

    /**
     * The type of landing page to show on the PayPal site for customer checkout.
     *
     * use one of constants defined in this class to set the value:
     * @see LANDING_PAGE_LOGIN
     * @see LANDING_PAGE_BILLING
     * @see LANDING_PAGE_NO_PREFERENCE
     * @var string | null
     * minLength: 1
     * maxLength: 13
     */
    public $landing_page = 'NO_PREFERENCE';

    /**
     * The shipping preference:<ul><li>Displays the shipping address to the customer.</li><li>Enables the customer to
     * choose an address on the PayPal site.</li><li>Restricts the customer from changing the address during the
     * payment-approval process.</li></ul>
     *
     * use one of constants defined in this class to set the value:
     * @see SHIPPING_PREFERENCE_GET_FROM_FILE
     * @see SHIPPING_PREFERENCE_NO_SHIPPING
     * @see SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS
     * @var string | null
     * minLength: 1
     * maxLength: 20
     */
    public $shipping_preference = 'GET_FROM_FILE';

    /**
     * Configures a <strong>Continue</strong> or <strong>Pay Now</strong> checkout flow.
     *
     * use one of constants defined in this class to set the value:
     * @see USER_ACTION_CONTINUE
     * @see USER_ACTION_PAY_NOW
     * @var string | null
     * minLength: 1
     * maxLength: 8
     */
    public $user_action = 'CONTINUE';

    /**
     * The customer and merchant payment preferences.
     *
     * @var PaymentMethod | null
     */
    public $payment_method;

    /**
     * The URL where the customer is redirected after the customer approves the payment.
     *
     * @var string | null
     */
    public $return_url;

    /**
     * The URL where the customer is redirected after the customer cancels the payment.
     *
     * @var string | null
     */
    public $cancel_url;

    /**
     * The internal client-generated token.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 19
     */
    public $payment_token;

    /**
     * Client configuration that captures the product flows and specific experiences that a user completes a paypal
     * transaction.
     *
     * @var ClientConfiguration | null
     */
    public $client_configuration;

    /**
     * Signals to vault the payment source upon successful validation.
     *
     * @var boolean | null
     */
    public $vault = false;

    /**
     * The payment source definition.
     *
     * @var PaymentSource2 | null
     */
    public $preferred_payment_source;

    /**
     * Provides additional details to process a payment using a `payment_source` that has been stored or is intended
     * to be stored (also referred to as stored_credential or card-on-file).<br/>Parameter
     * compatibility:<br/><ul><li>`payment_type=ONE_TIME` is compatible only with
     * `payment_initiator=CUSTOMER`.</li><li>`usage=FIRST` is compatible only with
     * `payment_initiator=CUSTOMER`.</li><li>`previous_transaction_reference` or
     * `previous_network_transaction_reference` is compatible only with `payment_initiator=MERCHANT`.</li><li>Only
     * one of the parameters - `previous_transaction_reference` and `previous_network_transaction_reference` - can be
     * present in the request.</li></ul>
     *
     * @var StoredPaymentSource | null
     */
    public $stored_payment_source;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->brand_name) || Assert::minLength(
            $this->brand_name,
            1,
            "brand_name in OrderApplicationContext must have minlength of 1 $within"
        );
        !isset($this->brand_name) || Assert::maxLength(
            $this->brand_name,
            127,
            "brand_name in OrderApplicationContext must have maxlength of 127 $within"
        );
        !isset($this->locale) || Assert::minLength(
            $this->locale,
            2,
            "locale in OrderApplicationContext must have minlength of 2 $within"
        );
        !isset($this->locale) || Assert::maxLength(
            $this->locale,
            10,
            "locale in OrderApplicationContext must have maxlength of 10 $within"
        );
        !isset($this->landing_page) || Assert::minLength(
            $this->landing_page,
            1,
            "landing_page in OrderApplicationContext must have minlength of 1 $within"
        );
        !isset($this->landing_page) || Assert::maxLength(
            $this->landing_page,
            13,
            "landing_page in OrderApplicationContext must have maxlength of 13 $within"
        );
        !isset($this->shipping_preference) || Assert::minLength(
            $this->shipping_preference,
            1,
            "shipping_preference in OrderApplicationContext must have minlength of 1 $within"
        );
        !isset($this->shipping_preference) || Assert::maxLength(
            $this->shipping_preference,
            20,
            "shipping_preference in OrderApplicationContext must have maxlength of 20 $within"
        );
        !isset($this->user_action) || Assert::minLength(
            $this->user_action,
            1,
            "user_action in OrderApplicationContext must have minlength of 1 $within"
        );
        !isset($this->user_action) || Assert::maxLength(
            $this->user_action,
            8,
            "user_action in OrderApplicationContext must have maxlength of 8 $within"
        );
        !isset($this->payment_method) || Assert::isInstanceOf(
            $this->payment_method,
            PaymentMethod::class,
            "payment_method in OrderApplicationContext must be instance of PaymentMethod $within"
        );
        !isset($this->payment_method) ||  $this->payment_method->validate(OrderExperienceContext::class);
        !isset($this->payment_token) || Assert::minLength(
            $this->payment_token,
            1,
            "payment_token in OrderApplicationContext must have minlength of 1 $within"
        );
        !isset($this->payment_token) || Assert::maxLength(
            $this->payment_token,
            19,
            "payment_token in OrderApplicationContext must have maxlength of 19 $within"
        );
        !isset($this->client_configuration) || Assert::isInstanceOf(
            $this->client_configuration,
            ClientConfiguration::class,
            "client_configuration in OrderApplicationContext must be instance of ClientConfiguration $within"
        );
        !isset($this->client_configuration) ||  $this->client_configuration->validate(OrderExperienceContext::class);
        !isset($this->preferred_payment_source) || Assert::isInstanceOf(
            $this->preferred_payment_source,
            PaymentSource2::class,
            "preferred_payment_source in OrderApplicationContext must be instance of PaymentSource2 $within"
        );
        !isset($this->preferred_payment_source) ||  $this->preferred_payment_source->validate(OrderExperienceContext::class);
        !isset($this->stored_payment_source) || Assert::isInstanceOf(
            $this->stored_payment_source,
            StoredPaymentSource::class,
            "stored_payment_source in OrderApplicationContext must be instance of StoredPaymentSource $within"
        );
        !isset($this->stored_payment_source) ||  $this->stored_payment_source->validate(OrderExperienceContext::class);
    }

    private function map(array $data)
    {
        if (isset($data['brand_name'])) {
            $this->brand_name = $data['brand_name'];
        }
        if (isset($data['locale'])) {
            $this->locale = $data['locale'];
        }
        if (isset($data['landing_page'])) {
            $this->landing_page = $data['landing_page'];
        }
        if (isset($data['shipping_preference'])) {
            $this->shipping_preference = $data['shipping_preference'];
        }
        if (isset($data['user_action'])) {
            $this->user_action = $data['user_action'];
        }
        if (isset($data['payment_method'])) {
            $this->payment_method = new PaymentMethod($data['payment_method']);
        }
        if (isset($data['return_url'])) {
            $this->return_url = $data['return_url'];
        }
        if (isset($data['cancel_url'])) {
            $this->cancel_url = $data['cancel_url'];
        }
        if (isset($data['payment_token'])) {
            $this->payment_token = $data['payment_token'];
        }
        if (isset($data['client_configuration'])) {
            $this->client_configuration = new ClientConfiguration($data['client_configuration']);
        }
        if (isset($data['vault'])) {
            $this->vault = $data['vault'];
        }
        if (isset($data['preferred_payment_source'])) {
            $this->preferred_payment_source = new PaymentSource2($data['preferred_payment_source']);
        }
        if (isset($data['stored_payment_source'])) {
            $this->stored_payment_source = new StoredPaymentSource($data['stored_payment_source']);
        }
    }

    public function __construct($data = null)
    {
        if (isset($data)) {
            $data = get_object_vars($data);
            $this->map($data);
        }
    }

    public function initPaymentMethod(): PaymentMethod
    {
        return $this->payment_method = new PaymentMethod();
    }

    public function initClientConfiguration(): ClientConfiguration
    {
        return $this->client_configuration = new ClientConfiguration();
    }

    public function initPreferredPaymentSource(): PaymentSource2
    {
        return $this->preferred_payment_source = new PaymentSource2();
    }

    public function initStoredPaymentSource(): StoredPaymentSource
    {
        return $this->stored_payment_source = new StoredPaymentSource();
    }
}
