<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Payments;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Refunds a captured payment, by ID. For a full refund, include an empty request body. For a partial refund,
 * include an <code>amount</code> object in the request body.
 *
 * generated from: refund_request.json
 */
class RefundRequest implements JsonSerializable
{
    use BaseModel;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money | null
     */
    public $amount;

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
     * The API caller-provided external ID. Used to reconcile API caller-initiated transactions with PayPal
     * transactions. Appears in transaction and settlement reports.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 127
     */
    public $custom_id;

    /**
     * The reason for the refund. Appears in both the payer's transaction history and the emails that the payer
     * receives.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $note_to_payer;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->amount) || Assert::isInstanceOf(
            $this->amount,
            Money::class,
            "amount in RefundRequest must be instance of Money $within"
        );
        !isset($this->amount) ||  $this->amount->validate(RefundRequest::class);
        !isset($this->invoice_id) || Assert::minLength(
            $this->invoice_id,
            1,
            "invoice_id in RefundRequest must have minlength of 1 $within"
        );
        !isset($this->invoice_id) || Assert::maxLength(
            $this->invoice_id,
            127,
            "invoice_id in RefundRequest must have maxlength of 127 $within"
        );
        !isset($this->custom_id) || Assert::minLength(
            $this->custom_id,
            1,
            "custom_id in RefundRequest must have minlength of 1 $within"
        );
        !isset($this->custom_id) || Assert::maxLength(
            $this->custom_id,
            127,
            "custom_id in RefundRequest must have maxlength of 127 $within"
        );
        !isset($this->note_to_payer) || Assert::minLength(
            $this->note_to_payer,
            1,
            "note_to_payer in RefundRequest must have minlength of 1 $within"
        );
        !isset($this->note_to_payer) || Assert::maxLength(
            $this->note_to_payer,
            255,
            "note_to_payer in RefundRequest must have maxlength of 255 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['amount'])) {
            $this->amount = new Money($data['amount']);
        }
        if (isset($data['invoice_id'])) {
            $this->invoice_id = $data['invoice_id'];
        }
        if (isset($data['custom_id'])) {
            $this->custom_id = $data['custom_id'];
        }
        if (isset($data['note_to_payer'])) {
            $this->note_to_payer = $data['note_to_payer'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initAmount(): Money
    {
        return $this->amount = new Money();
    }

    public function jsonSerialize(): object
    {
        $data = (array) $this;

        // Remove note_to_payer if it's null or empty
        if (!isset($this->note_to_payer) || $this->note_to_payer === '') {
            unset($data['note_to_payer']);
        }

        return (object) array_filter($data, static function ($var) {
            return isset($var);
        });
    }
}
