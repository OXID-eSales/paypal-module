<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The phone information.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-phone_with_type.json
 */
class PhoneWithType implements JsonSerializable
{
    use BaseModel;

    /**
     * The phone type.
     *
     * @var string | null
     */
    public $phone_type;

    /**
     * The phone number, in its canonical international [E.164 numbering plan
     * format](https://www.itu.int/rec/T-REC-E.164/en).
     *
     * @var Phone2
     */
    public $phone_number;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->phone_number, "phone_number in PhoneWithType must not be NULL $within");
        Assert::isInstanceOf(
            $this->phone_number,
            Phone2::class,
            "phone_number in PhoneWithType must be instance of Phone2 $within"
        );
         $this->phone_number->validate(PhoneWithType::class);
    }

    private function map(array $data)
    {
        if (isset($data['phone_type'])) {
            $this->phone_type = $data['phone_type'];
        }
        if (isset($data['phone_number'])) {
            $this->phone_number = new Phone2($data['phone_number']);
        }
    }

    public function __construct(array $data = null)
    {
        $this->phone_number = new Phone2();
        if (isset($data)) {
            $this->map($data);
        }
    }
}
