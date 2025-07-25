<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Information needed to pay using Pui.
 *
 */
class PuiRequest implements JsonSerializable
{
    use BaseModel;

    /**
     * The full name representation like Mr J Smith.
     *
     * @var string
     * minLength: 3
     * maxLength: 300
     */
    public $name;

    /**
     * The [two-character ISO 3166-1 code](/docs/integration/direct/rest/country-codes/) that identifies the country
     * or region.<blockquote><strong>Note:</strong> The country code for Great Britain is <code>GB</code> and not
     * <code>UK</code> as used in the top-level domain names for that country. Use the `C2` country code for China
     * worldwide for comparable uncontrolled price (CUP) method, bank card, and cross-border
     * transactions.</blockquote>
     *
     * @var string
     * minLength: 2
     * maxLength: 2
     */
    public $country_code;

    /**
     * The business identification code (BIC). In payments systems, a BIC is used to identify a specific business,
     * most commonly a bank.
     *
     * @var string | null
     * minLength: 8
     * maxLength: 11
     */
    public $bic;

    /**
     * The bank_name.
     *
     * @var string | null
     * minLength: 3
     * maxLength: 300
     */
    public $bank_name;

    /**
     * The IBAN.
     *
     * @var string | null
     * minLength: 22
     * maxLength: 22
     */
    public $iban;

    /**
     * The account_holder_name.
     *
     * @var string | null
     * minLength: 3
     * maxLength: 300
     */
    public $account_holder_name;

    /**
     * The payment_reference.
     *
     * @var string | null
     * minLength: 3
     * maxLength: 300
     */
    public $payment_reference;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->name, "name in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->name,
            3,
            "name in PuiRequest must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->name,
            300,
            "name in PuiRequest must have maxlength of 300 $within"
        );
        Assert::notNull($this->country_code, "country_code in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->country_code,
            2,
            "country_code in PuiRequest must have minlength of 2 $within"
        );
        Assert::maxLength(
            $this->country_code,
            2,
            "country_code in PuiRequest must have maxlength of 2 $within"
        );
        Assert::notNull($this->bic, "bic in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->bic,
            8,
            "bic in PuiRequest must have minlength of 8 $within"
        );
        Assert::maxLength(
            $this->bic,
            11,
            "bic in PuiRequest must have maxlength of 11 $within"
        );
        Assert::notNull($this->bank_name, "bank_name in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->bank_name,
            3,
            "bank_name in PuiRequest must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->bank_name,
            300,
            "bank_name in PuiRequest must have maxlength of 300 $within"
        );
        Assert::notNull($this->iban, "iban in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->iban,
            22,
            "iban in PuiRequest must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->iban,
            22,
            "iban in PuiRequest must have maxlength of 300 $within"
        );
        Assert::notNull($this->account_holder_name, "account_holder_name in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->account_holder_name,
            3,
            "account_holder_name in PuiRequest must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->account_holder_name,
            300,
            "account_holder_name in PuiRequest must have maxlength of 300 $within"
        );
        Assert::notNull($this->payment_reference, "payment_reference in PuiRequest must not be NULL $within");
        Assert::minLength(
            $this->payment_reference,
            3,
            "payment_reference in PuiRequest must have minlength of 3 $within"
        );
        Assert::maxLength(
            $this->payment_reference,
            300,
            "payment_reference in PuiRequest must have maxlength of 300 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['country_code'])) {
            $this->country_code = $data['country_code'];
        }
        if (isset($data['deposit_bank_details']['bic'])) {
            $this->bic = $data['deposit_bank_details']['bic'];
        }
        if (isset($data['deposit_bank_details']['bank_name'])) {
            $this->bank_name = $data['deposit_bank_details']['bank_name'];
        }
        if (isset($data['deposit_bank_details']['iban'])) {
            $this->iban = $data['deposit_bank_details']['iban'];
        }
        if (isset($data['deposit_bank_details']['account_holder_name'])) {
            $this->account_holder_name = $data['deposit_bank_details']['account_holder_name'];
        }
        if (isset($data['payment_reference'])) {
            $this->payment_reference = $data['payment_reference'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}