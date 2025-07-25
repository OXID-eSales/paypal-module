<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The currency and amount for a financial transaction, such as a balance or payment due.
 *
 * generated from:
 * MerchantsCommonComponentsSpecification-v1-schema-common_components-v4-schema-json-openapi-2.0-money.json
 */
class Money implements JsonSerializable
{
    use BaseModel;

    /**
     * The [three-character ISO-4217 currency code](/docs/integration/direct/rest/currency-codes/) that identifies
     * the currency.
     *
     * @var string
     * minLength: 3
     * maxLength: 3
     */
    public $currency_code;

    /**
     * The value, which might be:<ul><li>An integer for currencies like `JPY` that are not typically
     * fractional.</li><li>A decimal fraction for currencies like `TND` that are subdivided into
     * thousandths.</li></ul>For the required number of decimal places for a currency code, see [Currency
     * Codes](/docs/integration/direct/rest/currency-codes/).
     *
     * @var string
     * maxLength: 32
     */
    public $value;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->currency_code, "currency_code in Money must not be NULL $within");
        Assert::minLength(
            $this->currency_code,
            3,
            "currency_code in Money must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->currency_code,
            3,
            "currency_code in Money must have maxlength of 3 $within"
        );
        Assert::notNull($this->value, "value in Money must not be NULL $within");
        Assert::maxLength(
            $this->value,
            32,
            "value in Money must have maxlength of 32 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['currency_code'])) {
            $this->currency_code = $data['currency_code'];
        }
        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
