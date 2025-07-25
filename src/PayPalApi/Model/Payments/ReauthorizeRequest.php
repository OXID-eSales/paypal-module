<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Payments;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Reauthorizes an authorized PayPal account payment, by ID. To ensure that funds are still available,
 * reauthorize a payment after its initial three-day honor period expires. You can reauthorize a payment only
 * once from days four to 29.<br/><br/>If 30 days have transpired since the date of the original authorization,
 * you must create an authorized payment instead of reauthorizing the original authorized payment.<br/><br/>A
 * reauthorized payment itself has a new honor period of three days.<br/><br/>You can reauthorize an authorized
 * payment once for up to 115% of the original authorized amount, not to exceed an increase of $75
 * USD.<br/><br/>Supports only the `amount` request parameter.<blockquote><strong>Note:</strong> This request is
 * currently not supported for Partner use cases.</blockquote>
 *
 * generated from: reauthorize_request.json
 */
class ReauthorizeRequest implements JsonSerializable
{
    use BaseModel;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money | null
     */
    public $amount;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->amount) || Assert::isInstanceOf(
            $this->amount,
            Money::class,
            "amount in ReauthorizeRequest must be instance of Money $within"
        );
        !isset($this->amount) ||  $this->amount->validate(ReauthorizeRequest::class);
    }

    private function map(array $data)
    {
        if (isset($data['amount'])) {
            $this->amount = new Money($data['amount']);
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
}
