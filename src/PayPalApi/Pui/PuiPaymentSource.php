<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPalApi\Pui;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Name3;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;

class PuiPaymentSource extends PaymentSource implements JsonSerializable
{
    use BaseModel;

    /**
     * The name of the party.
     *
     * @var Name3 | null
     */
    public $name;

    /**
     * The phone information.
     *
     * @var Phone | null
     */
    public $phone;

    /**
     * The stand-alone date, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6). To
     * represent special legal values, such as a date of birth, you should use dates with no associated time or
     * time-zone data. Whenever possible, use the standard `date_time` type. This regular expression does not
     * validate all dates. For example, February 31 is valid and nothing is known about leap years.
     *
     * @var string | null
     * minLength: 10
     * maxLength: 10
     */
    public $birth_date;

    /**
     * The portable international postal address. Maps to
     * [AddressValidationMetadata](https://github.com/googlei18n/libaddressinput/wiki/AddressValidationMetadata) and
     * HTML 5.1 [Autofilling form controls: the autocomplete
     * attribute](https://www.w3.org/TR/html51/sec-forms.html#autofilling-form-controls-the-autocomplete-attribute).
     *
     * @var AddressPortable
     */
    public $billing_address;

    /** @var string */
    public $email;

    /**
     * @var ExperienceContext
     */
    public $experience_context;
}
