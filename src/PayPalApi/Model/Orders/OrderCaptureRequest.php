<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Completes an capture payment for an order.
 *
 * generated from: order_capture_request.json
 */
class OrderCaptureRequest implements JsonSerializable
{
    use BaseModel;

    /**
     * The payment source definition.
     *
     * @var PaymentSource | null
     */
    public $payment_source;


    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->payment_source) || Assert::isInstanceOf(
            $this->payment_source,
            PaymentSource::class,
            "payment_source in OrderCaptureRequest must be instance of PaymentSource $within"
        );
        !isset($this->payment_source) ||  $this->payment_source->validate(OrderCaptureRequest::class);
        !isset($this->payment_source->experience_context) || Assert::isInstanceOf(
            $this->payment_source->experience_context,
            OrderExperienceContext2::class,
            "application_context in OrderCaptureRequest must be instance of OrderApplicationContext2 $within"
        );
        !isset($this->payment_source->experience_context) ||  $this->payment_source->experience_context->validate(OrderCaptureRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['payment_source'])) {
            $this->payment_source = new PaymentSource($data['payment_source']);
        }
        if (isset($data['experience_context'])) {
            $this->payment_source->experience_context = new OrderExperienceContext2($data['experience_context']);
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initPaymentSource(): PaymentSource
    {
        return $this->payment_source = new PaymentSource();
    }

    public function initApplicationContext(): OrderExperienceContext2
    {
        return $this->payment_source->experience_context = new OrderExperienceContext2();
    }
}
