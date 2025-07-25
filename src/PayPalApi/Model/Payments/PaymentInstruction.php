<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Payments;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Any additional payment instructions to be consider during payment processing. This processing instruction is
 * applicable for Capturing an order or Authorizing an Order.
 *
 * generated from: MerchantCommonComponentsSpecification-v1-schema-payment_instruction.json
 */
class PaymentInstruction implements JsonSerializable
{
    use BaseModel;

    /** The funds are released to the merchant immediately. */
    public const DISBURSEMENT_MODE_INSTANT = 'INSTANT';

    /** The funds are held for a finite number of days. The actual duration depends on the region and type of integration. You can release the funds through a referenced payout. Otherwise, the funds disbursed automatically after the specified duration. */
    public const DISBURSEMENT_MODE_DELAYED = 'DELAYED';

    /**
     * An array of various fees, commissions, tips, or donations. This field is only applicable to merchants that
     * been enabled for PayPal Commerce Platform for Marketplaces and Platforms capability.
     *
     * @var PlatformFee[]
     * maxItems: 0
     * maxItems: 1
     */
    public $platform_fees;

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
     * This field is only enabled for selected merchants/partners to use and provides the ability to trigger a
     * specific pricing rate/plan for a payment transaction. The list of eligible 'payee_pricing_tier_id' would be
     * provided to you by your Account Manager. Specifying values other than the one provided to you by your account
     * manager would result in an error.
     *
     * @var string | null
     * minLength: 1
     * maxLength: 20
     */
    public $payee_pricing_tier_id;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->platform_fees, "platform_fees in PaymentInstruction must not be NULL $within");
        Assert::minCount(
            $this->platform_fees,
            0,
            "platform_fees in PaymentInstruction must have min. count of 0 $within"
        );
        Assert::maxCount(
            $this->platform_fees,
            1,
            "platform_fees in PaymentInstruction must have max. count of 1 $within"
        );
        Assert::isArray(
            $this->platform_fees,
            "platform_fees in PaymentInstruction must be array $within"
        );
        if (isset($this->platform_fees)) {
            foreach ($this->platform_fees as $item) {
                $item->validate(PaymentInstruction::class);
            }
        }
        !isset($this->payee_pricing_tier_id) || Assert::minLength(
            $this->payee_pricing_tier_id,
            1,
            "payee_pricing_tier_id in PaymentInstruction must have minlength of 1 $within"
        );
        !isset($this->payee_pricing_tier_id) || Assert::maxLength(
            $this->payee_pricing_tier_id,
            20,
            "payee_pricing_tier_id in PaymentInstruction must have maxlength of 20 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['platform_fees'])) {
            $this->platform_fees = [];
            foreach ($data['platform_fees'] as $item) {
                $this->platform_fees[] = new PlatformFee($item);
            }
        }
        if (isset($data['disbursement_mode'])) {
            $this->disbursement_mode = $data['disbursement_mode'];
        }
        if (isset($data['payee_pricing_tier_id'])) {
            $this->payee_pricing_tier_id = $data['payee_pricing_tier_id'];
        }
    }

    public function __construct(array $data = null)
    {
        $this->platform_fees = [];
        if (isset($data)) {
            $this->map($data);
        }
    }
}