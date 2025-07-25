<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Customizes the payer confirmation experience.
 *
 * generated from: order_confirm_application_context.json
 */
class OrderConfirmApplicationContext implements JsonSerializable
{
    use BaseModel;

    /**
     * Label to present to your payer as part of the PayPal hosted web experience.
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
     * The URL where the customer is redirected after the customer approves the payment.
     *
     * @var string | null
     * minLength: 10
     * maxLength: 4000
     */
    public $return_url;

    /**
     * The URL where the customer is redirected after the customer cancels the payment.
     *
     * @var string | null
     * minLength: 10
     * maxLength: 4000
     */
    public $cancel_url;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->brand_name) || Assert::minLength(
            $this->brand_name,
            1,
            "brand_name in OrderConfirmApplicationContext must have minlength of 1 $within"
        );
        !isset($this->brand_name) || Assert::maxLength(
            $this->brand_name,
            127,
            "brand_name in OrderConfirmApplicationContext must have maxlength of 127 $within"
        );
        !isset($this->locale) || Assert::minLength(
            $this->locale,
            2,
            "locale in OrderConfirmApplicationContext must have minlength of 2 $within"
        );
        !isset($this->locale) || Assert::maxLength(
            $this->locale,
            10,
            "locale in OrderConfirmApplicationContext must have maxlength of 10 $within"
        );
        !isset($this->return_url) || Assert::minLength(
            $this->return_url,
            10,
            "return_url in OrderConfirmApplicationContext must have minlength of 10 $within"
        );
        !isset($this->return_url) || Assert::maxLength(
            $this->return_url,
            4000,
            "return_url in OrderConfirmApplicationContext must have maxlength of 4000 $within"
        );
        !isset($this->cancel_url) || Assert::minLength(
            $this->cancel_url,
            10,
            "cancel_url in OrderConfirmApplicationContext must have minlength of 10 $within"
        );
        !isset($this->cancel_url) || Assert::maxLength(
            $this->cancel_url,
            4000,
            "cancel_url in OrderConfirmApplicationContext must have maxlength of 4000 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['brand_name'])) {
            $this->brand_name = $data['brand_name'];
        }
        if (isset($data['locale'])) {
            $this->locale = $data['locale'];
        }
        if (isset($data['return_url'])) {
            $this->return_url = $data['return_url'];
        }
        if (isset($data['cancel_url'])) {
            $this->cancel_url = $data['cancel_url'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
