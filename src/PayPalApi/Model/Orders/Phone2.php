<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The phone number, in its canonical international [E.164 numbering plan
 * format](https://www.itu.int/rec/T-REC-E.164/en).
 *
 * generated from:
 * customized_x_unsupported_3449_MerchantsCommonComponentsSpecification-v1-schema-common_components-v3-schema-json-openapi-2.0-phone.json
 */
class Phone2 implements JsonSerializable
{
    use BaseModel;

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

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->national_number, "national_number in Phone2 must not be NULL $within");
        Assert::minLength(
            $this->national_number,
            1,
            "national_number in Phone2 must have minlength of 1 $within"
        );
        Assert::maxLength(
            $this->national_number,
            14,
            "national_number in Phone2 must have maxlength of 14 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['national_number'])) {
            $this->national_number = $data['national_number'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
