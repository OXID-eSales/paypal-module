<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;
use Webmozart\Assert\Assert;

/**
 * Payer confirms the intent to pay for the Order using the provided payment source.
 *
 * generated from: confirm_order_request.json
 */
class ConfirmOrderRequest implements JsonSerializable
{
    use BaseModel;

    /** The API caller saves the order for future payment processing by making an explicit <code>v2/checkout/orders/id/save</code> call after the payer approves the order. */
    public const PROCESSING_INSTRUCTION_ORDER_SAVED_EXPLICITLY = 'ORDER_SAVED_EXPLICITLY';

    /** PayPal implicitly saves the order on behalf of the API caller after the payer approves the order. Note that this option is not currently supported. */
    public const PROCESSING_INSTRUCTION_ORDER_SAVED_ON_BUYER_APPROVAL = 'ORDER_SAVED_ON_BUYER_APPROVAL';

    /** API Caller expects the Order to be auto completed (i.e. for PayPal to authorize or capture depending on the intent) on completion of payer approval. This option is not relevant for payment_source that typically do not require a payer approval or interaction. This option is currently only available for the following payment_source: Alipay, Bancontact, BLIK, eps, giropay, Multibanco, MyBank, P24, PayU, POLi, Sofort, Trustly, TrustPay, Verkkopankki, WeChat Pay */
    public const PROCESSING_INSTRUCTION_ORDER_COMPLETE_ON_PAYMENT_APPROVAL = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    /** The API caller intends to authorize <code>v2/checkout/orders/id/authorize</code> or capture <code>v2/checkout/orders/id/capture</code> after the payer approves the order. */
    public const PROCESSING_INSTRUCTION_NO_INSTRUCTION = 'NO_INSTRUCTION';

    /**
     * The payment source definition.
     *
     * @var PaymentSource
     */
    public $payment_source;

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


    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->payment_source, "payment_source in ConfirmOrderRequest must not be NULL $within");
        Assert::isInstanceOf(
            $this->payment_source,
            PaymentSource::class,
            "payment_source in ConfirmOrderRequest must be instance of PaymentSource $within"
        );
         $this->payment_source->validate(ConfirmOrderRequest::class);
        !isset($this->processing_instruction) || Assert::minLength(
            $this->processing_instruction,
            1,
            "processing_instruction in ConfirmOrderRequest must have minlength of 1 $within"
        );
        !isset($this->processing_instruction) || Assert::maxLength(
            $this->processing_instruction,
            36,
            "processing_instruction in ConfirmOrderRequest must have maxlength of 36 $within"
        );
        !isset($this->payment_source->experience_context) || Assert::isInstanceOf(
            $this->payment_source->experience_context,
            OrderExperienceContext::class,
            "experience_context in ConfirmOrderRequest must be instance of OrderConfirmApplicationContext $within"
        );
        !isset($this->payment_source->experience_context) ||  $this->payment_source->experience_context->validate(ConfirmOrderRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['payment_source'])) {
            $this->payment_source = new PaymentSource($data['payment_source']);
        }
        if (isset($data['processing_instruction'])) {
            $this->processing_instruction = $data['processing_instruction'];
        }
        if (isset($data['experience_context'])) {
            $this->payment_source->experience_context = new OrderConfirmExperienceContext($data['experience_context']);
        }
    }

    public function __construct(array $data = null)
    {
        $this->payment_source = new PaymentSource();
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initApplicationContext(): OrderConfirmExperienceContext
    {
        return $this->payment_source->experience_context = new OrderConfirmExperienceContext();
    }
}
