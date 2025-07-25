<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The order request details.
 *
 * generated from: order_request.json
 */
class OrderRequest implements JsonSerializable
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

    /**
     * The intent to either capture payment immediately or authorize a payment for an order after order creation.
     *
     * use one of constants defined in this class to set the value:
     * @see INTENT_CAPTURE
     * @see INTENT_AUTHORIZE
     * @var string
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
     * An array of purchase units. Each purchase unit establishes a contract between a payer and the payee. Each
     * purchase unit represents either a full or partial order that the payer intends to purchase from the payee.
     *
     * @var PurchaseUnitRequest[]
     * maxItems: 1
     * maxItems: 10
     */
    public $purchase_units;

    /**
     * The payment source definition.
     *
     * @var PaymentSource | null
     */
    public $payment_source;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->intent, "intent in OrderRequest must not be NULL $within");
        !isset($this->processing_instruction) || Assert::minLength(
            $this->processing_instruction,
            1,
            "processing_instruction in OrderRequest must have minlength of 1 $within"
        );
        !isset($this->processing_instruction) || Assert::maxLength(
            $this->processing_instruction,
            36,
            "processing_instruction in OrderRequest must have maxlength of 36 $within"
        );
        !isset($this->payer) || Assert::isInstanceOf(
            $this->payer,
            Payer::class,
            "payer in OrderRequest must be instance of Payer $within"
        );
        !isset($this->payer) ||  $this->payer->validate(OrderRequest::class);
        Assert::notNull($this->purchase_units, "purchase_units in OrderRequest must not be NULL $within");
        Assert::minCount(
            $this->purchase_units,
            1,
            "purchase_units in OrderRequest must have min. count of 1 $within"
        );
        Assert::maxCount(
            $this->purchase_units,
            10,
            "purchase_units in OrderRequest must have max. count of 10 $within"
        );
        Assert::isArray(
            $this->purchase_units,
            "purchase_units in OrderRequest must be array $within"
        );
        if (isset($this->purchase_units)) {
            foreach ($this->purchase_units as $item) {
                $item->validate(OrderRequest::class);
            }
        }
        !isset($this->payment_source) || Assert::isInstanceOf(
            $this->payment_source,
            PaymentSource::class,
            "payment_source in OrderRequest must be instance of PaymentSource $within"
        );
        !isset($this->payment_source) ||  $this->payment_source->validate(OrderRequest::class);
        !isset($this->payment_source->experience_context) || Assert::isInstanceOf(
            $this->payment_source->experience_context,
            OrderExperienceContext::class,
            "experience_context in OrderRequest must be instance of ExperienceContext $within"
        );
        !isset($this->payment_source->experience_context) ||  $this->payment_source->experience_context->validate(OrderRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['intent'])) {
            $this->intent = $data['intent'];
        }
        if (isset($data['processing_instruction'])) {
            $this->processing_instruction = $data['processing_instruction'];
        }
        if (isset($data['payer'])) {
            $this->payer = new Payer($data['payer']);
        }
        if (isset($data['purchase_units'])) {
            $this->purchase_units = [];
            foreach ($data['purchase_units'] as $item) {
                $this->purchase_units[] = new PurchaseUnitRequest($item);
            }
        }
        if (isset($data['payment_source'])) {
            $this->payment_source = new PaymentSource($data['payment_source']);
        }
    }

    public function __construct(array $data = null)
    {
        $this->purchase_units = [];
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initPayer(): Payer
    {
        return $this->payer = new Payer();
    }

    public function initPaymentSource(): PaymentSource
    {
        return $this->payment_source = new PaymentSource();
    }

    public function initExperienceContext(): OrderExperienceContext
    {
        return $this->payment_source->experience_context = new OrderExperienceContext();
    }
}
