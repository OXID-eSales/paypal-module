<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * A captured payment.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-capture.json
 */
class Capture extends CaptureStatus implements JsonSerializable
{
    use BaseModel;

    /** The funds are released to the merchant immediately. */
    public const DISBURSEMENT_MODE_INSTANT = 'INSTANT';

    /** The funds are held for a finite number of days. The actual duration depends on the region and type of integration. You can release the funds through a referenced payout. Otherwise, the funds disbursed automatically after the specified duration. */
    public const DISBURSEMENT_MODE_DELAYED = 'DELAYED';

    /**
     * The PayPal-generated ID for the captured payment.
     *
     * @var string | null
     */
    public $id;

    /**
     * The sender side ID of the capture transaction. This ID will be populated only for Cross GEO transactions.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 22
     */
    public $sender_transaction_id;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money | null
     */
    public $amount;

    /**
     * Information about the parent transaction.
     *
     * @var ParentTransaction | null
     */
    public $parent_transaction;

    /**
     * The API caller-provided external invoice number for this order. Appears in both the payer's transaction
     * history and the emails that the payer receives.
     *
     * @var string | null
     */
    public $invoice_id;

    /**
     * The API caller-provided external ID. Used to reconcile API caller-initiated transactions with PayPal
     * transactions. Appears in transaction and settlement reports.
     *
     * @var string | null
     * maxLength: 127
     */
    public $custom_id;

    /**
     * The level of protection offered as defined by [PayPal Seller Protection for
     * Merchants](https://www.paypal.com/us/webapps/mpp/security/seller-protection).
     *
     * @var SellerProtection | null
     */
    public $seller_protection;

    /**
     * Indicates whether you can make additional captures against the authorized payment. Set to `true` if you do not
     * intend to capture additional payments against the authorization. Set to `false` if you intend to capture
     * additional payments against the authorization.
     *
     * @var boolean | null
     */
    public $final_capture = false;

    /**
     * The detailed breakdown of the capture activity. This is not available for transactions that are in pending
     * state.
     *
     * @var SellerReceivableBreakdown | null
     */
    public $seller_receivable_breakdown;

    /**
     * The funds that are held on behalf of the merchant.
     *
     * use one of constants defined in this class to set the value:
     * @see DISBURSEMENT_MODE_INSTANT
     * @see DISBURSEMENT_MODE_DELAYED
     * @var string | null
     */
    public $disbursement_mode = 'INSTANT';

    /**
     * The error details.
     *
     * @var mixed | null
     */
    public $error;

    /**
     * An array of related [HATEOAS links](/docs/api/reference/api-responses/#hateoas-links).
     *
     * @var array | null
     */
    public $links;

    /**
     * The processor information. Might be required for payment requests, such as direct credit card transactions.
     *
     * @var ProcessorResponse | null
     */
    public $processor_response;

    /**
     * The date and time, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6).
     * Seconds are required while fractional seconds are optional.<blockquote><strong>Note:</strong> The regular
     * expression provides guidance but does not reject all invalid dates.</blockquote>
     *
     * @var string | null
     * minLength: 20
     * maxLength: 64
     */
    public $create_time;

    /**
     * The date and time, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6).
     * Seconds are required while fractional seconds are optional.<blockquote><strong>Note:</strong> The regular
     * expression provides guidance but does not reject all invalid dates.</blockquote>
     *
     * @var string | null
     * minLength: 20
     * maxLength: 64
     */
    public $update_time;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->sender_transaction_id) || Assert::minLength(
            $this->sender_transaction_id,
            1,
            "sender_transaction_id in Capture must have minlength of 1 $within"
        );
        !isset($this->sender_transaction_id) || Assert::maxLength(
            $this->sender_transaction_id,
            22,
            "sender_transaction_id in Capture must have maxlength of 22 $within"
        );
        !isset($this->amount) || Assert::isInstanceOf(
            $this->amount,
            Money::class,
            "amount in Capture must be instance of Money $within"
        );
        !isset($this->amount) ||  $this->amount->validate(Capture::class);
        !isset($this->parent_transaction) || Assert::isInstanceOf(
            $this->parent_transaction,
            ParentTransaction::class,
            "parent_transaction in Capture must be instance of ParentTransaction $within"
        );
        !isset($this->parent_transaction) ||  $this->parent_transaction->validate(Capture::class);
        !isset($this->custom_id) || Assert::maxLength(
            $this->custom_id,
            127,
            "custom_id in Capture must have maxlength of 127 $within"
        );
        !isset($this->seller_protection) || Assert::isInstanceOf(
            $this->seller_protection,
            SellerProtection::class,
            "seller_protection in Capture must be instance of SellerProtection $within"
        );
        !isset($this->seller_protection) ||  $this->seller_protection->validate(Capture::class);
        !isset($this->seller_receivable_breakdown) || Assert::isInstanceOf(
            $this->seller_receivable_breakdown,
            SellerReceivableBreakdown::class,
            "seller_receivable_breakdown in Capture must be instance of SellerReceivableBreakdown $within"
        );
        !isset($this->seller_receivable_breakdown) ||  $this->seller_receivable_breakdown->validate(Capture::class);
        !isset($this->links) || Assert::isArray(
            $this->links,
            "links in Capture must be array $within"
        );
        !isset($this->processor_response) || Assert::isInstanceOf(
            $this->processor_response,
            ProcessorResponse::class,
            "processor_response in Capture must be instance of ProcessorResponse $within"
        );
        !isset($this->processor_response) ||  $this->processor_response->validate(Capture::class);
        !isset($this->create_time) || Assert::minLength(
            $this->create_time,
            20,
            "create_time in Capture must have minlength of 20 $within"
        );
        !isset($this->create_time) || Assert::maxLength(
            $this->create_time,
            64,
            "create_time in Capture must have maxlength of 64 $within"
        );
        !isset($this->update_time) || Assert::minLength(
            $this->update_time,
            20,
            "update_time in Capture must have minlength of 20 $within"
        );
        !isset($this->update_time) || Assert::maxLength(
            $this->update_time,
            64,
            "update_time in Capture must have maxlength of 64 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        if (isset($data['sender_transaction_id'])) {
            $this->sender_transaction_id = $data['sender_transaction_id'];
        }
        if (isset($data['amount'])) {
            $this->amount = new Money($data['amount']);
        }
        if (isset($data['parent_transaction'])) {
            $this->parent_transaction = new ParentTransaction($data['parent_transaction']);
        }
        if (isset($data['invoice_id'])) {
            $this->invoice_id = $data['invoice_id'];
        }
        if (isset($data['custom_id'])) {
            $this->custom_id = $data['custom_id'];
        }
        if (isset($data['seller_protection'])) {
            $this->seller_protection = new SellerProtection($data['seller_protection']);
        }
        if (isset($data['final_capture'])) {
            $this->final_capture = $data['final_capture'];
        }
        if (isset($data['seller_receivable_breakdown'])) {
            $this->seller_receivable_breakdown = new SellerReceivableBreakdown($data['seller_receivable_breakdown']);
        }
        if (isset($data['disbursement_mode'])) {
            $this->disbursement_mode = $data['disbursement_mode'];
        }
        if (isset($data['error'])) {
            $this->error = $data['error'];
        }
        if (isset($data['links'])) {
            $this->links = [];
            foreach ($data['links'] as $item) {
                $this->links[] = $item;
            }
        }
        if (isset($data['processor_response'])) {
            $this->processor_response = new ProcessorResponse($data['processor_response']);
        }
        if (isset($data['create_time'])) {
            $this->create_time = $data['create_time'];
        }
        if (isset($data['update_time'])) {
            $this->update_time = $data['update_time'];
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

    public function initParentTransaction(): ParentTransaction
    {
        return $this->parent_transaction = new ParentTransaction();
    }

    public function initSellerProtection(): SellerProtection
    {
        return $this->seller_protection = new SellerProtection();
    }

    public function initSellerReceivableBreakdown(): SellerReceivableBreakdown
    {
        return $this->seller_receivable_breakdown = new SellerReceivableBreakdown();
    }

    public function initProcessorResponse(): ProcessorResponse
    {
        return $this->processor_response = new ProcessorResponse();
    }
}
