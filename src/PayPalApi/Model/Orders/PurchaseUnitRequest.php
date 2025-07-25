<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The purchase unit request. Includes required information for the payment contract.
 *
 * generated from: purchase_unit_request.json
 */
class PurchaseUnitRequest implements JsonSerializable
{
    use BaseModel;

    /**
     * The API caller-provided external ID for the purchase unit. Required for multiple purchase units when you must
     * update the order through `PATCH`. If you omit this value and the order contains only one purchase unit, PayPal
     * sets this value to `default`.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 256
     */
    public $reference_id;

    /**
     * The total order amount with an optional breakdown that provides details, such as the total item amount, total
     * tax amount, shipping, handling, insurance, and discounts, if any.<br/>If you specify `amount.breakdown`, the
     * amount equals `item_total` plus `tax_total` plus `shipping` plus `handling` plus `insurance` minus
     * `shipping_discount` minus discount.<br/>The amount must be a positive number. For listed of supported
     * currencies and decimal precision, see the PayPal REST APIs <a
     * href="/docs/integration/direct/rest/currency-codes/">Currency Codes</a>.
     *
     * @var AmountWithBreakdown
     */
    public $amount;

    /**
     * The merchant who receives the funds and fulfills the order. The merchant is also known as the payee.
     *
     * @var Payee | null
     */
    public $payee;

    /**
     * Any additional payment instructions to be consider during payment processing. This processing instruction is
     * applicable for Capturing an order or Authorizing an Order.
     *
     * @var PaymentInstruction | null
     */
    public $payment_instruction;

    /**
     * The purchase description. The maximum length of the character is dependent on the type of characters used. The
     * character length is specified assuming a US ASCII character. Depending on type of character; (e.g. accented
     * character, Japanese characters) the number of characters that that can be specified as input might not equal
     * the permissible max length.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $description;

    /**
     * The API caller-provided external ID. Used to reconcile client transactions with PayPal transactions. Appears
     * in transaction and settlement reports but is not visible to the payer.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $custom_id;

    /**
     * The API caller-provided external invoice number for this order. Appears in both the payer's transaction
     * history and the emails that the payer receives.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $invoice_id;

    /**
     * The soft descriptor is the dynamic text used to construct the statement descriptor that appears on a payer's
     * card statement.<br><br>If an Order is paid using the "PayPal Wallet", the statement descriptor will appear in
     * following format on the payer's card statement:
     * <code><var>PAYPAL_prefix</var>+(space)+<var>merchant_descriptor</var>+(space)+
     * <var>soft_descriptor</var></code><blockquote><strong>Note:</strong> The merchant descriptor is the descriptor
     * of the merchant’s payment receiving preferences which can be seen by logging into the merchant account
     * https://www.sandbox.paypal.com/businessprofile/settings/info/edit</blockquote>The <code>PAYPAL</code> prefix
     * uses 8 characters. Only the first 22 characters will be displayed in the statement. <br>For example,
     * if:<ul><li>The PayPal prefix toggle is <code>PAYPAL *</code>.</li><li>The merchant descriptor in the profile
     * is <code>Janes Gift</code>.</li><li>The soft descriptor is <code>800-123-1234</code>.</li></ul>Then, the
     * statement descriptor on the card is <code>PAYPAL * Janes Gift 80</code>.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 22
     */
    public $soft_descriptor;

    /**
     * An array of items that the customer purchases from the merchant.
     *
     * @var Item[] | null
     */
    public $items;

    /**
     * The shipping details.
     *
     * @var ShippingDetail | null
     */
    public $shipping;

    /**
     * The supplementary data.
     *
     * @var SupplementaryData | null
     */
    public $supplementary_data;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->reference_id) || Assert::minLength(
            $this->reference_id,
            1,
            "reference_id in PurchaseUnitRequest must have minlength of 1 $within"
        );
        !isset($this->reference_id) || Assert::maxLength(
            $this->reference_id,
            256,
            "reference_id in PurchaseUnitRequest must have maxlength of 256 $within"
        );
        Assert::notNull($this->amount, "amount in PurchaseUnitRequest must not be NULL $within");
        Assert::isInstanceOf(
            $this->amount,
            AmountWithBreakdown::class,
            "amount in PurchaseUnitRequest must be instance of AmountWithBreakdown $within"
        );
         $this->amount->validate(PurchaseUnitRequest::class);
        !isset($this->payee) || Assert::isInstanceOf(
            $this->payee,
            Payee::class,
            "payee in PurchaseUnitRequest must be instance of Payee $within"
        );
        !isset($this->payee) ||  $this->payee->validate(PurchaseUnitRequest::class);
        !isset($this->payment_instruction) || Assert::isInstanceOf(
            $this->payment_instruction,
            PaymentInstruction::class,
            "payment_instruction in PurchaseUnitRequest must be instance of PaymentInstruction $within"
        );
        !isset($this->payment_instruction) ||  $this->payment_instruction->validate(PurchaseUnitRequest::class);
        !isset($this->description) || Assert::minLength(
            $this->description,
            1,
            "description in PurchaseUnitRequest must have minlength of 1 $within"
        );
        !isset($this->description) || Assert::maxLength(
            $this->description,
            127,
            "description in PurchaseUnitRequest must have maxlength of 127 $within"
        );
        !isset($this->custom_id) || Assert::minLength(
            $this->custom_id,
            1,
            "custom_id in PurchaseUnitRequest must have minlength of 1 $within"
        );
        !isset($this->custom_id) || Assert::maxLength(
            $this->custom_id,
            127,
            "custom_id in PurchaseUnitRequest must have maxlength of 127 $within"
        );
        !isset($this->invoice_id) || Assert::minLength(
            $this->invoice_id,
            1,
            "invoice_id in PurchaseUnitRequest must have minlength of 1 $within"
        );
        !isset($this->invoice_id) || Assert::maxLength(
            $this->invoice_id,
            127,
            "invoice_id in PurchaseUnitRequest must have maxlength of 127 $within"
        );
        !isset($this->soft_descriptor) || Assert::minLength(
            $this->soft_descriptor,
            1,
            "soft_descriptor in PurchaseUnitRequest must have minlength of 1 $within"
        );
        !isset($this->soft_descriptor) || Assert::maxLength(
            $this->soft_descriptor,
            22,
            "soft_descriptor in PurchaseUnitRequest must have maxlength of 22 $within"
        );
        !isset($this->items) || Assert::isArray(
            $this->items,
            "items in PurchaseUnitRequest must be array $within"
        );
        if (isset($this->items)) {
            foreach ($this->items as $item) {
                $item->validate(PurchaseUnitRequest::class);
            }
        }
        !isset($this->shipping) || Assert::isInstanceOf(
            $this->shipping,
            ShippingDetail::class,
            "shipping in PurchaseUnitRequest must be instance of ShippingDetail $within"
        );
        !isset($this->shipping) ||  $this->shipping->validate(PurchaseUnitRequest::class);
        !isset($this->supplementary_data) || Assert::isInstanceOf(
            $this->supplementary_data,
            SupplementaryData::class,
            "supplementary_data in PurchaseUnitRequest must be instance of SupplementaryData $within"
        );
        !isset($this->supplementary_data) ||  $this->supplementary_data->validate(PurchaseUnitRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['reference_id'])) {
            $this->reference_id = $data['reference_id'];
        }
        if (isset($data['amount'])) {
            $this->amount = new AmountWithBreakdown($data['amount']);
        }
        if (isset($data['payee'])) {
            $this->payee = new Payee($data['payee']);
        }
        if (isset($data['payment_instruction'])) {
            $this->payment_instruction = new PaymentInstruction($data['payment_instruction']);
        }
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
        if (isset($data['custom_id'])) {
            $this->custom_id = $data['custom_id'];
        }
        if (isset($data['invoice_id'])) {
            $this->invoice_id = $data['invoice_id'];
        }
        if (isset($data['soft_descriptor'])) {
            $this->soft_descriptor = $data['soft_descriptor'];
        }
        if (isset($data['items'])) {
            $this->items = [];
            foreach ($data['items'] as $item) {
                $this->items[] = new Item($item);
            }
        }
        if (isset($data['shipping'])) {
            $this->shipping = new ShippingDetail($data['shipping']);
        }
        if (isset($data['supplementary_data'])) {
            $this->supplementary_data = new SupplementaryData($data['supplementary_data']);
        }
    }

    public function __construct(array $data = null)
    {
        $this->amount = new AmountWithBreakdown();
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initPayee(): Payee
    {
        return $this->payee = new Payee();
    }

    public function initPaymentInstruction(): PaymentInstruction
    {
        return $this->payment_instruction = new PaymentInstruction();
    }

    public function initShipping(): ShippingDetail
    {
        return $this->shipping = new ShippingDetail();
    }

    public function initSupplementaryData(): SupplementaryData
    {
        return $this->supplementary_data = new SupplementaryData();
    }
}
