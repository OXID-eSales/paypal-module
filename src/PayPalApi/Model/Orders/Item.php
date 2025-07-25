<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The details for the items to be purchased.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-item.json
 */
class Item implements JsonSerializable
{
    use BaseModel;

    /** Goods that are stored, delivered, and used in their electronic format. This value is not currently supported for API callers that leverage the <a href="https://www.paypal.com/us/webapps/mpp/commerce-platform">PayPal for Commerce Platform</a> product. */
    public const CATEGORY_DIGITAL_GOODS = 'DIGITAL_GOODS';

    /** A tangible item that can be shipped with proof of delivery. */
    public const CATEGORY_PHYSICAL_GOODS = 'PHYSICAL_GOODS';

    /** A contribution or gift for which no good or service is exchanged, usually to a not for profit organization. */
    public const CATEGORY_DONATION = 'DONATION';

    /**
     * The item name or title.
     *
     * @var string
     * minLength: 1
     * maxLength: 127
     */
    public $name;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money
     */
    public $unit_amount;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money | null
     */
    public $tax;

    /**
     * The item quantity. Must be a whole number.
     *
     * @var string
     * maxLength: 10
     */
    public $quantity;

    /**
     * The item tax rate. Must be a whole number.
     *
     * @var string
     * maxLength: 10
     */
    public $tax_rate;

    /**
     * The detailed item description.
     *
     * @var string | null
     * maxLength: 127
     */
    public $description;

    /**
     * The stock keeping unit (SKU) for the item.
     *
     * @var string | null
     * maxLength: 127
     */
    public $sku;

    /**
     * The item category type.
     *
     * use one of constants defined in this class to set the value:
     * @see CATEGORY_DIGITAL_GOODS
     * @see CATEGORY_PHYSICAL_GOODS
     * @see CATEGORY_DONATION
     * @var string | null
     * minLength: 1
     * maxLength: 20
     */
    public $category;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->name, "name in Item must not be NULL $within");
        Assert::minLength(
            $this->name,
            1,
            "name in Item must have minlength of 1 $within"
        );
        Assert::maxLength(
            $this->name,
            127,
            "name in Item must have maxlength of 127 $within"
        );
        Assert::notNull($this->unit_amount, "unit_amount in Item must not be NULL $within");
        Assert::isInstanceOf(
            $this->unit_amount,
            Money::class,
            "unit_amount in Item must be instance of Money $within"
        );
         $this->unit_amount->validate(Item::class);
        !isset($this->tax) || Assert::isInstanceOf(
            $this->tax,
            Money::class,
            "tax in Item must be instance of Money $within"
        );
        !isset($this->tax) ||  $this->tax->validate(Item::class);
        Assert::notNull($this->quantity, "quantity in Item must not be NULL $within");
        Assert::maxLength(
            $this->quantity,
            10,
            "quantity in Item must have maxlength of 10 $within"
        );
        Assert::maxLength(
            $this->tax_rate,
            10,
            "tax_rate in Item must have maxlength of 10 $within"
        );
        !isset($this->description) || Assert::maxLength(
            $this->description,
            127,
            "description in Item must have maxlength of 127 $within"
        );
        !isset($this->sku) || Assert::maxLength(
            $this->sku,
            127,
            "sku in Item must have maxlength of 127 $within"
        );
        !isset($this->category) || Assert::minLength(
            $this->category,
            1,
            "category in Item must have minlength of 1 $within"
        );
        !isset($this->category) || Assert::maxLength(
            $this->category,
            20,
            "category in Item must have maxlength of 20 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['unit_amount'])) {
            $this->unit_amount = new Money($data['unit_amount']);
        }
        if (isset($data['tax'])) {
            $this->tax = new Money($data['tax']);
        }
        if (isset($data['quantity'])) {
            $this->quantity = $data['quantity'];
        }
        if (isset($data['tax_rate'])) {
            $this->tax_rate = $data['tax_rate'];
        }
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
        if (isset($data['sku'])) {
            $this->sku = $data['sku'];
        }
        if (isset($data['category'])) {
            $this->category = $data['category'];
        }
    }

    public function __construct(array $data = null)
    {
        $this->unit_amount = new Money();
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initTax(): Money
    {
        return $this->tax = new Money();
    }
}
