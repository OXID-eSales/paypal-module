<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Payments;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The platform or partner fee, commission, or brokerage fee that is associated with the transaction. Not a
 * separate or isolated transaction leg from the external perspective. The platform fee is limited in scope and
 * is always associated with the original payment for the purchase unit.
 *
 * generated from: MerchantCommonComponentsSpecification-v1-schema-platform_fee.json
 */
class PlatformFee implements JsonSerializable
{
    use BaseModel;

    /**
     * The currency and amount for a financial transaction, such as a balance or payment due.
     *
     * @var Money
     */
    public $amount;

    /**
     * The details for the merchant who receives the funds and fulfills the order. The merchant is also known as the
     * payee.
     *
     * @var PayeeBase | null
     */
    public $payee;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->amount, "amount in PlatformFee must not be NULL $within");
        Assert::isInstanceOf(
            $this->amount,
            Money::class,
            "amount in PlatformFee must be instance of Money $within"
        );
         $this->amount->validate(PlatformFee::class);
        !isset($this->payee) || Assert::isInstanceOf(
            $this->payee,
            PayeeBase::class,
            "payee in PlatformFee must be instance of PayeeBase $within"
        );
        !isset($this->payee) ||  $this->payee->validate(PlatformFee::class);
    }

    private function map(array $data)
    {
        if (isset($data['amount'])) {
            $this->amount = new Money($data['amount']);
        }
        if (isset($data['payee'])) {
            $this->payee = new PayeeBase($data['payee']);
        }
    }

    public function __construct(array $data = null)
    {
        $this->amount = new Money();
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initPayee(): PayeeBase
    {
        return $this->payee = new PayeeBase();
    }
}