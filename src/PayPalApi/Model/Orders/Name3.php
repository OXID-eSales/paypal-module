<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The name of the party.
 *
 * generated from:
 * customized_x_unsupported_7388_MerchantsCommonComponentsSpecification-v1-schema-common_components-v3-schema-json-openapi-2.0-name.json
 */
class Name3 implements JsonSerializable
{
    use BaseModel;

    /**
     * When the party is a person, the party's given, or first, name.
     *
     * @var string | null
     * maxLength: 140
     */
    public $given_name;

    /**
     * When the party is a person, the party's surname or family name. Also known as the last name. Required when the
     * party is a person. Use also to store multiple surnames including the matronymic, or mother's, surname.
     *
     * @var string | null
     * maxLength: 140
     */
    public $surname;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->given_name) || Assert::maxLength(
            $this->given_name,
            140,
            "given_name in Name3 must have maxlength of 140 $within"
        );
        !isset($this->surname) || Assert::maxLength(
            $this->surname,
            140,
            "surname in Name3 must have maxlength of 140 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['given_name'])) {
            $this->given_name = $data['given_name'];
        }
        if (isset($data['surname'])) {
            $this->surname = $data['surname'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
