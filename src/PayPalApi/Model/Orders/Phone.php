<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The phone number in its canonical international [E.164 numbering plan
 * format](https://www.itu.int/rec/T-REC-E.164/en).
 *
 * generated from:
 * MerchantsCommonComponentsSpecification-v1-schema-common_components-v4-schema-json-openapi-2.0-phone.json
 */
class Phone implements JsonSerializable
{
    use BaseModel;

    /**
     * The country calling code (CC), in its canonical international [E.164 numbering plan
     * format](https://www.itu.int/rec/T-REC-E.164/en). The combined length of the CC and the national number must
     * not be greater than 15 digits. The national number consists of a national destination code (NDC) and
     * subscriber number (SN).
     *
     * @var string
     * minLength: 1
     * maxLength: 3
     */
    public $country_code;

    /**
     * The national number, in its canonical international [E.164 numbering plan
     * format](https://www.itu.int/rec/T-REC-E.164/en). The combined length of the country calling code (CC) and the
     * national number must not be greater than 15 digits. The national number consists of a national destination
     * code (NDC) and subscriber number (SN).
     *
     * @var string
     * minLength: 1
     * maxLength: 14
     */
    public $national_number;

    /**
     * The extension number.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 15
     */
    public $extension_number;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->country_code, "country_code in Phone must not be NULL $within");
        Assert::minLength(
            $this->country_code,
            1,
            "country_code in Phone must have minlength of 1 $within"
        );
        Assert::maxLength(
            $this->country_code,
            3,
            "country_code in Phone must have maxlength of 3 $within"
        );
        Assert::notNull($this->national_number, "national_number in Phone must not be NULL $within");
        Assert::minLength(
            $this->national_number,
            1,
            "national_number in Phone must have minlength of 1 $within"
        );
        Assert::maxLength(
            $this->national_number,
            14,
            "national_number in Phone must have maxlength of 14 $within"
        );
        !isset($this->extension_number) || Assert::minLength(
            $this->extension_number,
            1,
            "extension_number in Phone must have minlength of 1 $within"
        );
        !isset($this->extension_number) || Assert::maxLength(
            $this->extension_number,
            15,
            "extension_number in Phone must have maxlength of 15 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['country_code'])) {
            $this->country_code = $data['country_code'];
        }
        if (isset($data['national_number'])) {
            $this->national_number = $data['national_number'];
        }
        if (isset($data['extension_number'])) {
            $this->extension_number = $data['extension_number'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
