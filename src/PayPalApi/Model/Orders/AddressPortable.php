<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The portable international postal address. Maps to
 * [AddressValidationMetadata](https://github.com/googlei18n/libaddressinput/wiki/AddressValidationMetadata) and
 * HTML 5.1 [Autofilling form controls: the autocomplete
 * attribute](https://www.w3.org/TR/html51/sec-forms.html#autofilling-form-controls-the-autocomplete-attribute).
 *
 * generated from:
 * MerchantsCommonComponentsSpecification-v1-schema-common_components-v3-schema-json-openapi-2.0-address_portable.json
 */
class AddressPortable implements JsonSerializable
{
    use BaseModel;

    /**
     * The first line of the address. For example, number or street. For example, `173 Drury Lane`. Required for data
     * entry and compliance and risk checks. Must contain the full address.
     *
     * @var string | null
     * maxLength: 300
     */
    public $address_line_1;

    /**
     * The second line of the address. For example, suite or apartment number.
     *
     * @var string | null
     * maxLength: 300
     */
    public $address_line_2;

    /**
     * The third line of the address, if needed. For example, a street complement for Brazil, direction text, such as
     * `next to Walmart`, or a landmark in an Indian address.
     *
     * @var string | null
     * maxLength: 100
     */
    public $address_line_3;

    /**
     * The neighborhood, ward, or district. Smaller than `admin_area_level_3` or `sub_locality`. Value is:<ul><li>The
     * postal sorting code for Guernsey and many French territories, such as French Guiana.</li><li>The fine-grained
     * administrative levels in China.</li></ul>
     *
     * @var string | null
     * maxLength: 100
     */
    public $admin_area_4;

    /**
     * A sub-locality, suburb, neighborhood, or district. Smaller than `admin_area_level_2`. Value is:<ul><li>Brazil.
     * Suburb, bairro, or neighborhood.</li><li>India. Sub-locality or district. Street name information is not
     * always available but a sub-locality or district can be a very small area.</li></ul>
     *
     * @var string | null
     * maxLength: 100
     */
    public $admin_area_3;

    /**
     * A city, town, or village. Smaller than `admin_area_level_1`.
     *
     * @var string | null
     * maxLength: 120
     */
    public $admin_area_2;

    /**
     * The highest level sub-division in a country, which is usually a province, state, or ISO-3166-2 subdivision.
     * Format for postal delivery. For example, `CA` and not `California`. Value, by country, is:<ul><li>UK. A
     * county.</li><li>US. A state.</li><li>Canada. A province.</li><li>Japan. A prefecture.</li><li>Switzerland. A
     * kanton.</li></ul>
     *
     * @var string | null
     * maxLength: 300
     */
    public $admin_area_1;

    /**
     * The postal code, which is the zip code or equivalent. Typically required for countries with a postal code or
     * an equivalent. See [postal code](https://en.wikipedia.org/wiki/Postal_code).
     *
     * @var string | null
     * maxLength: 60
     */
    public $postal_code;

    /**
     * The [two-character ISO 3166-1 code](/docs/integration/direct/rest/country-codes/) that identifies the country
     * or region.<blockquote><strong>Note:</strong> The country code for Great Britain is <code>GB</code> and not
     * <code>UK</code> as used in the top-level domain names for that country. Use the `C2` country code for China
     * worldwide for comparable uncontrolled price (CUP) method, bank card, and cross-border
     * transactions.</blockquote>
     *
     * @var string
     * minLength: 2
     * maxLength: 2
     */
    public $country_code;

    /**
     * The non-portable additional address details that are sometimes needed for compliance, risk, or other scenarios
     * where fine-grain address information might be needed. Not portable with common third party and open source.
     * Redundant with core fields.<br/>For example, `address_portable.address_line_1` is usually a combination of
     * `address_details.street_number`, `street_name`, and `street_type`.
     *
     * @var AddressPortableAddressDetails | null
     */
    public $address_details;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->address_line_1) || Assert::maxLength(
            $this->address_line_1,
            300,
            "address_line_1 in AddressPortable must have maxlength of 300 $within"
        );
        !isset($this->address_line_2) || Assert::maxLength(
            $this->address_line_2,
            300,
            "address_line_2 in AddressPortable must have maxlength of 300 $within"
        );
        !isset($this->address_line_3) || Assert::maxLength(
            $this->address_line_3,
            100,
            "address_line_3 in AddressPortable must have maxlength of 100 $within"
        );
        !isset($this->admin_area_4) || Assert::maxLength(
            $this->admin_area_4,
            100,
            "admin_area_4 in AddressPortable must have maxlength of 100 $within"
        );
        !isset($this->admin_area_3) || Assert::maxLength(
            $this->admin_area_3,
            100,
            "admin_area_3 in AddressPortable must have maxlength of 100 $within"
        );
        !isset($this->admin_area_2) || Assert::maxLength(
            $this->admin_area_2,
            120,
            "admin_area_2 in AddressPortable must have maxlength of 120 $within"
        );
        !isset($this->admin_area_1) || Assert::maxLength(
            $this->admin_area_1,
            300,
            "admin_area_1 in AddressPortable must have maxlength of 300 $within"
        );
        !isset($this->postal_code) || Assert::maxLength(
            $this->postal_code,
            60,
            "postal_code in AddressPortable must have maxlength of 60 $within"
        );
        Assert::notNull($this->country_code, "country_code in AddressPortable must not be NULL $within");
        Assert::minLength(
            $this->country_code,
            2,
            "country_code in AddressPortable must have minlength of 2 $within"
        );
        Assert::maxLength(
            $this->country_code,
            2,
            "country_code in AddressPortable must have maxlength of 2 $within"
        );
        !isset($this->address_details) || Assert::isInstanceOf(
            $this->address_details,
            AddressPortableAddressDetails::class,
            "address_details in AddressPortable must be instance of AddressPortableAddressDetails $within"
        );
        !isset($this->address_details) ||  $this->address_details->validate(AddressPortable::class);
    }

    private function map(array $data)
    {
        if (isset($data['address_line_1'])) {
            $this->address_line_1 = $data['address_line_1'];
        }
        if (isset($data['address_line_2'])) {
            $this->address_line_2 = $data['address_line_2'];
        }
        if (isset($data['address_line_3'])) {
            $this->address_line_3 = $data['address_line_3'];
        }
        if (isset($data['admin_area_4'])) {
            $this->admin_area_4 = $data['admin_area_4'];
        }
        if (isset($data['admin_area_3'])) {
            $this->admin_area_3 = $data['admin_area_3'];
        }
        if (isset($data['admin_area_2'])) {
            $this->admin_area_2 = $data['admin_area_2'];
        }
        if (isset($data['admin_area_1'])) {
            $this->admin_area_1 = $data['admin_area_1'];
        }
        if (isset($data['postal_code'])) {
            $this->postal_code = $data['postal_code'];
        }
        if (isset($data['country_code'])) {
            $this->country_code = $data['country_code'];
        }
        if (isset($data['address_details'])) {
            $this->address_details = new AddressPortableAddressDetails($data['address_details']);
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initAddressDetails(): AddressPortableAddressDetails
    {
        return $this->address_details = new AddressPortableAddressDetails();
    }
}
