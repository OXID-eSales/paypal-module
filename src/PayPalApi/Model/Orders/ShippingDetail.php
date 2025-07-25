<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The shipping details.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-shipping_detail.json
 */
class ShippingDetail implements JsonSerializable
{
    use BaseModel;

    /** The payer intends to receive the items at a specified address. */
    public const TYPE_SHIPPING = 'SHIPPING';

    /** The payer intends to pick up the items from the payee in person. */
    public const TYPE_PICKUP_IN_PERSON = 'PICKUP_IN_PERSON';

    /**
     * The name of the party.
     *
     * @var Name4 | null
     */
    public $name;

    /**
     * The internationalized email address.<blockquote><strong>Note:</strong> Up to 64 characters are allowed before
     * and 255 characters are allowed after the <code>@</code> sign. However, the generally accepted maximum length
     * for an email address is 254 characters. The pattern verifies that an unquoted <code>@</code> sign
     * exists.</blockquote>
     *
     * @var string | null
     * minLength: 3
     * maxLength: 254
     */
    public $email_address;

    /**
     * The phone number, in its canonical international [E.164 numbering plan
     * format](https://www.itu.int/rec/T-REC-E.164/en).
     *
     * @var Phone2 | null
     */
    public $phone_number;

    /**
     * The method by which the payer wants to get their items from the payee e.g shipping, in-person pickup. Either
     * type or options but not both may be present.
     *
     * use one of constants defined in this class to set the value:
     * @see TYPE_SHIPPING
     * @see TYPE_PICKUP_IN_PERSON
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $type;

    /**
     * An array of shipping options that the payee or merchant offers to the payer to ship or pick up their items.
     *
     * @var ShippingOption[] | null
     * maxItems: 10
     */
    public $options;

    /**
     * The portable international postal address. Maps to
     * [AddressValidationMetadata](https://github.com/googlei18n/libaddressinput/wiki/AddressValidationMetadata) and
     * HTML 5.1 [Autofilling form controls: the autocomplete
     * attribute](https://www.w3.org/TR/html51/sec-forms.html#autofilling-form-controls-the-autocomplete-attribute).
     *
     * @var AddressPortable3 | null
     */
    public $address;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->name) || Assert::isInstanceOf(
            $this->name,
            Name4::class,
            "name in ShippingDetail must be instance of Name4 $within"
        );
        !isset($this->name) ||  $this->name->validate(ShippingDetail::class);
        !isset($this->email_address) || Assert::minLength(
            $this->email_address,
            3,
            "email_address in ShippingDetail must have minlength of 3 $within"
        );
        !isset($this->email_address) || Assert::maxLength(
            $this->email_address,
            254,
            "email_address in ShippingDetail must have maxlength of 254 $within"
        );
        !isset($this->phone_number) || Assert::isInstanceOf(
            $this->phone_number,
            Phone2::class,
            "phone_number in ShippingDetail must be instance of Phone2 $within"
        );
        !isset($this->phone_number) ||  $this->phone_number->validate(ShippingDetail::class);
        !isset($this->type) || Assert::minLength(
            $this->type,
            1,
            "type in ShippingDetail must have minlength of 1 $within"
        );
        !isset($this->type) || Assert::maxLength(
            $this->type,
            255,
            "type in ShippingDetail must have maxlength of 255 $within"
        );
        !isset($this->options) || Assert::maxCount(
            $this->options,
            10,
            "options in ShippingDetail must have max. count of 10 $within"
        );
        !isset($this->options) || Assert::isArray(
            $this->options,
            "options in ShippingDetail must be array $within"
        );
        if (isset($this->options)) {
            foreach ($this->options as $item) {
                $item->validate(ShippingDetail::class);
            }
        }
        !isset($this->address) || Assert::isInstanceOf(
            $this->address,
            AddressPortable3::class,
            "address in ShippingDetail must be instance of AddressPortable3 $within"
        );
        !isset($this->address) ||  $this->address->validate(ShippingDetail::class);
    }

    private function map(array $data)
    {
        if (isset($data['name'])) {
            $this->name = new Name4($data['name']);
        }
        if (isset($data['email_address'])) {
            $this->email_address = $data['email_address'];
        }
        if (isset($data['phone_number'])) {
            $this->phone_number = new Phone2($data['phone_number']);
        }
        if (isset($data['type'])) {
            $this->type = $data['type'];
        }
        if (isset($data['options'])) {
            $this->options = [];
            foreach ($data['options'] as $item) {
                $this->options[] = new ShippingOption($item);
            }
        }
        if (isset($data['address'])) {
            $this->address = new AddressPortable3($data['address']);
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initName(): Name4
    {
        return $this->name = new Name4();
    }

    public function initPhoneNumber(): Phone2
    {
        return $this->phone_number = new Phone2();
    }

    public function initAddress(): AddressPortable3
    {
        return $this->address = new AddressPortable3();
    }
}
