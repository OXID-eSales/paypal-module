<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The order details.
 *
 * generated from: order.json
 */
class Order extends ActivityTimestamps implements JsonSerializable
{
    use BaseModel;

    /** The merchant intends to capture payment immediately after the customer makes a payment. */
    public const INTENT_CAPTURE = 'CAPTURE';

    /** The merchant intends to authorize a payment and place funds on hold after the customer makes a payment. Authorized payments are best captured within three days of authorization but are available to capture for up to 29 days. After the three-day honor period, the original authorized payment expires and you must re-authorize the payment. You must make a separate request to capture payments on demand. This intent is not supported when you have more than one `purchase_unit` within your order. */
    public const INTENT_AUTHORIZE = 'AUTHORIZE';

    /** The API caller saves the order for future payment processing by making an explicit <code>v2/checkout/orders/id/save</code> call after the payer approves the order. */
    public const PROCESSING_INSTRUCTION_ORDER_SAVED_EXPLICITLY = 'ORDER_SAVED_EXPLICITLY';

    /** PayPal implicitly saves the order on behalf of the API caller after the payer approves the order. Note that this option is not currently supported. */
    public const PROCESSING_INSTRUCTION_ORDER_SAVED_ON_BUYER_APPROVAL = 'ORDER_SAVED_ON_BUYER_APPROVAL';

    /** API Caller expects the Order to be auto completed (i.e. for PayPal to authorize or capture depending on the intent) on completion of payer approval. This option is not relevant for payment_source that typically do not require a payer approval or interaction. This option is currently only available for the following payment_source: Alipay, Bancontact, BLIK, eps, giropay, Multibanco, MyBank, P24, PayU, POLi, Sofort, Trustly, TrustPay, Verkkopankki, WeChat Pay */
    public const PROCESSING_INSTRUCTION_ORDER_COMPLETE_ON_PAYMENT_APPROVAL = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    /** The API caller intends to authorize <code>v2/checkout/orders/id/authorize</code> or capture <code>v2/checkout/orders/id/capture</code> after the payer approves the order. */
    public const PROCESSING_INSTRUCTION_NO_INSTRUCTION = 'NO_INSTRUCTION';

    /** The order was created with the specified context. */
    public const STATUS_CREATED = 'CREATED';

    /** The order was saved and persisted. The order status continues to be in progress until a capture is made with <code>final_capture = true</code> for all purchase units within the order. */
    public const STATUS_SAVED = 'SAVED';

    /** The customer approved the payment through the PayPal wallet or another form of guest or unbranded payment. For example, a card, bank account, or so on. */
    public const STATUS_APPROVED = 'APPROVED';

    /** All purchase units in the order are voided. */
    public const STATUS_VOIDED = 'VOIDED';

    /** The payment was authorized or the authorized payment was captured for the order. */
    public const STATUS_COMPLETED = 'COMPLETED';

    /** The order requires an action from the payer (e.g. 3DS authentication). Redirect the payer to the "rel":"payer-action" HATEOAS link returned as part of the response prior to authorizing or capturing the order. */
    public const STATUS_PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';

    /** Some of the 'purchase_units' within the Order could not be successfully authorized or captured. This status is only applicable if you have multiple 'purchase_units' for an order. */
    public const STATUS_PARTIALLY_COMPLETED = 'PARTIALLY_COMPLETED';

    /**
     * The ID of the order.
     *
     * @var string | null
     */
    public $id;

    /**
     * The payment source used to fund the payment.
     *
     * @var PaymentSourceResponse | null
     */
    public $payment_source;

    /**
     * The intent to either capture payment immediately or authorize a payment for an order after order creation.
     *
     * use one of constants defined in this class to set the value:
     * @see INTENT_CAPTURE
     * @see INTENT_AUTHORIZE
     * @var string | null
     */
    public $intent;

    /**
     * The instruction to process an order.
     *
     * use one of constants defined in this class to set the value:
     * @see PROCESSING_INSTRUCTION_ORDER_SAVED_EXPLICITLY
     * @see PROCESSING_INSTRUCTION_ORDER_SAVED_ON_BUYER_APPROVAL
     * @see PROCESSING_INSTRUCTION_ORDER_COMPLETE_ON_PAYMENT_APPROVAL
     * @see PROCESSING_INSTRUCTION_NO_INSTRUCTION
     * @var string | null
     * minLength: 1
     * maxLength: 36
     */
    public $processing_instruction = 'NO_INSTRUCTION';

    /**
     * The customer who approves and pays for the order. The customer is also known as the payer.
     *
     * @var Payer | null
     */
    public $payer;

    /**
     * The date and time, in [Internet date and time format](https://tools.ietf.org/html/rfc3339#section-5.6).
     * Seconds are required while fractional seconds are optional.<blockquote><strong>Note:</strong> The regular
     * expression provides guidance but does not reject all invalid dates.</blockquote>
     *
     * @var string | null
     * minLength: 20
     * maxLength: 64
     */
    public $expiration_time;

    /**
     * An array of purchase units. Each purchase unit establishes a contract between a customer and merchant. Each
     * purchase unit represents either a full or partial order that the customer intends to purchase from the
     * merchant.
     *
     * @var PurchaseUnit[]
     * maxItems: 1
     * maxItems: 10
     */
    public $purchase_units;

    /**
     * The order status.
     *
     * use one of constants defined in this class to set the value:
     * @see STATUS_CREATED
     * @see STATUS_SAVED
     * @see STATUS_APPROVED
     * @see STATUS_VOIDED
     * @see STATUS_COMPLETED
     * @see STATUS_PAYER_ACTION_REQUIRED
     * @see STATUS_PARTIALLY_COMPLETED
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $status;

    /**
     * An array of request-related HATEOAS links. To complete payer approval, use the `approve` link to redirect the
     * payer. The API caller has 3 hours (default setting, this which can be changed by your account manager to
     * 24/48/72 hours to accommodate your use case) from the time the order is created, to redirect your payer. Once
     * redirected, the API caller has 3 hours for the payer to approve the order and either authorize or capture the
     * order. If you are not using the PayPal JavaScript SDK to initiate PayPal Checkout (in context) ensure that you
     * include `application_context.return_url` is specified or you will get "We're sorry, Things don't appear to be
     * working at the moment" after the payer approves the payment.
     *
     * @var array | null
     */
    public $links;

    /**
     * The details about the payer-selected credit financing offer.
     *
     * @var CreditFinancingOffer | null
     */
    public $credit_financing_offer;


    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->payment_source) || Assert::isInstanceOf(
            $this->payment_source,
            PaymentSourceResponse::class,
            "payment_source in Order must be instance of PaymentSourceResponse $within"
        );
        !isset($this->payment_source) ||  $this->payment_source->validate(Order::class);
        !isset($this->processing_instruction) || Assert::minLength(
            $this->processing_instruction,
            1,
            "processing_instruction in Order must have minlength of 1 $within"
        );
        !isset($this->processing_instruction) || Assert::maxLength(
            $this->processing_instruction,
            36,
            "processing_instruction in Order must have maxlength of 36 $within"
        );
        !isset($this->payer) || Assert::isInstanceOf(
            $this->payer,
            Payer::class,
            "payer in Order must be instance of Payer $within"
        );
        !isset($this->payer) ||  $this->payer->validate(Order::class);
        !isset($this->expiration_time) || Assert::minLength(
            $this->expiration_time,
            20,
            "expiration_time in Order must have minlength of 20 $within"
        );
        !isset($this->expiration_time) || Assert::maxLength(
            $this->expiration_time,
            64,
            "expiration_time in Order must have maxlength of 64 $within"
        );
        Assert::notNull($this->purchase_units, "purchase_units in Order must not be NULL $within");
        Assert::minCount(
            $this->purchase_units,
            1,
            "purchase_units in Order must have min. count of 1 $within"
        );
        Assert::maxCount(
            $this->purchase_units,
            10,
            "purchase_units in Order must have max. count of 10 $within"
        );
        Assert::isArray(
            $this->purchase_units,
            "purchase_units in Order must be array $within"
        );
        if (isset($this->purchase_units)) {
            foreach ($this->purchase_units as $item) {
                $item->validate(Order::class);
            }
        }
        !isset($this->status) || Assert::minLength(
            $this->status,
            1,
            "status in Order must have minlength of 1 $within"
        );
        !isset($this->status) || Assert::maxLength(
            $this->status,
            255,
            "status in Order must have maxlength of 255 $within"
        );
        !isset($this->links) || Assert::isArray(
            $this->links,
            "links in Order must be array $within"
        );
        !isset($this->credit_financing_offer) || Assert::isInstanceOf(
            $this->credit_financing_offer,
            CreditFinancingOffer::class,
            "credit_financing_offer in Order must be instance of CreditFinancingOffer $within"
        );
        !isset($this->credit_financing_offer) ||  $this->credit_financing_offer->validate(Order::class);
        !isset($this->payment_source->experience_context) || Assert::isInstanceOf(
            $this->payment_source->experience_context,
            OrderExperienceContext::class,
            "experience_context in Order must be instance of OrderApplicationContext2 $within"
        );
        !isset($this->payment_source->experience_context) ||  $this->payment_source->experience_context->validate(Order::class);
    }

    private function map(array $data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        if (isset($data['payment_source'])) {
            $this->payment_source = new PaymentSourceResponse($data['payment_source']);
        }
        if (isset($data['intent'])) {
            $this->intent = $data['intent'];
        }
        if (isset($data['processing_instruction'])) {
            $this->processing_instruction = $data['processing_instruction'];
        }
        if (isset($data['payer'])) {
            $this->payer = new Payer($data['payer']);
        }
        if (isset($data['expiration_time'])) {
            $this->expiration_time = $data['expiration_time'];
        }
        if (isset($data['purchase_units'])) {
            $this->purchase_units = [];
            foreach ($data['purchase_units'] as $item) {
                $this->purchase_units[] = new PurchaseUnit($item);
            }
        }
        if (isset($data['status'])) {
            $this->status = $data['status'];
        }
        if (isset($data['links'])) {
            $this->links = [];
            foreach ($data['links'] as $item) {
                $this->links[] = $item;
            }
        }
        if (isset($data['credit_financing_offer'])) {
            $this->credit_financing_offer = new CreditFinancingOffer($data['credit_financing_offer']);
        }
        if (isset($data['experience_context'])) {
            $this->payment_source->experience_context = new OrderExperienceContext($data['experience_context']);
        }
    }

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        $this->purchase_units = [];
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initPaymentSource(): PaymentSourceResponse
    {
        return $this->payment_source = new PaymentSourceResponse();
    }

    public function initPayer(): Payer
    {
        return $this->payer = new Payer();
    }

    public function initCreditFinancingOffer(): CreditFinancingOffer
    {
        return $this->credit_financing_offer = new CreditFinancingOffer();
    }

    public function initApplicationContext(): OrderExperienceContext2
    {
        return $this->payment_source->experience_context = new OrderExperienceContext2();
    }
}
