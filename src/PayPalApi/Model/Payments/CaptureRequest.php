<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Payments;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Captures either a portion or the full authorized amount of an authorized payment.
 *
 * generated from: capture_request.json
 */
class CaptureRequest extends SupplementaryPurchaseData implements JsonSerializable
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
     * Indicates whether you can make additional captures against the authorized payment. Set to `true` if you do not
     * intend to capture additional payments against the authorization. Set to `false` if you intend to capture
     * additional payments against the authorization.
     *
     * @var boolean | null
     */
    public $final_capture = 'false';

    /**
     * Any additional payment instructions to be consider during payment processing. This processing instruction is
     * applicable for Capturing an order or Authorizing an Order.
     *
     * @var PaymentInstruction | null
     */
    public $payment_instruction;

    /**
     * An informational note about this settlement. Appears in both the payer's transaction history and the emails
     * that the payer receives.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $note_to_payer;

    /**
     * The payment descriptor on the payer's account statement.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 22
     */
    public $soft_descriptor;

    /**
     * The supplementary data.
     *
     * @var SupplementaryData | null
     */
    public $supplementary_data;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->amount) || Assert::isInstanceOf(
            $this->amount,
            Money::class,
            "amount in CaptureRequest must be instance of Money $within"
        );
        !isset($this->amount) ||  $this->amount->validate(CaptureRequest::class);
        !isset($this->invoice_id) || Assert::minLength(
            $this->invoice_id,
            1,
            "invoice_id in CaptureRequest must have minlength of 1 $within"
        );
        !isset($this->invoice_id) || Assert::maxLength(
            $this->invoice_id,
            127,
            "invoice_id in CaptureRequest must have maxlength of 127 $within"
        );
        !isset($this->payment_instruction) || Assert::isInstanceOf(
            $this->payment_instruction,
            PaymentInstruction::class,
            "payment_instruction in CaptureRequest must be instance of PaymentInstruction $within"
        );
        !isset($this->payment_instruction) ||  $this->payment_instruction->validate(CaptureRequest::class);
        !isset($this->note_to_payer) || Assert::minLength(
            $this->note_to_payer,
            1,
            "note_to_payer in CaptureRequest must have minlength of 1 $within"
        );
        !isset($this->note_to_payer) || Assert::maxLength(
            $this->note_to_payer,
            255,
            "note_to_payer in CaptureRequest must have maxlength of 255 $within"
        );
        !isset($this->soft_descriptor) || Assert::minLength(
            $this->soft_descriptor,
            1,
            "soft_descriptor in CaptureRequest must have minlength of 1 $within"
        );
        !isset($this->soft_descriptor) || Assert::maxLength(
            $this->soft_descriptor,
            22,
            "soft_descriptor in CaptureRequest must have maxlength of 22 $within"
        );
        !isset($this->supplementary_data) || Assert::isInstanceOf(
            $this->supplementary_data,
            SupplementaryData::class,
            "supplementary_data in CaptureRequest must be instance of SupplementaryData $within"
        );
        !isset($this->supplementary_data) ||  $this->supplementary_data->validate(CaptureRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['amount'])) {
            $this->amount = new Money($data['amount']);
        }
        if (isset($data['invoice_id'])) {
            $this->invoice_id = $data['invoice_id'];
        }
        if (isset($data['final_capture'])) {
            $this->final_capture = $data['final_capture'];
        }
        if (isset($data['payment_instruction'])) {
            $this->payment_instruction = new PaymentInstruction($data['payment_instruction']);
        }
        if (isset($data['note_to_payer'])) {
            $this->note_to_payer = $data['note_to_payer'];
        }
        if (isset($data['soft_descriptor'])) {
            $this->soft_descriptor = $data['soft_descriptor'];
        }
        if (isset($data['supplementary_data'])) {
            $this->supplementary_data = new SupplementaryData($data['supplementary_data']);
        }
    }

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initAmount(): Money
    {
        return $this->amount = new Money();
    }

    public function initPaymentInstruction(): PaymentInstruction
    {
        return $this->payment_instruction = new PaymentInstruction();
    }

    public function initSupplementaryData(): SupplementaryData
    {
        return $this->supplementary_data = new SupplementaryData();
    }
}
