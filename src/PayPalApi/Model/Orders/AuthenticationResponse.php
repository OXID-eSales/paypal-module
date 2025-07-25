<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Results of Authentication such as 3D Secure.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-authentication_response.json
 */
class AuthenticationResponse implements JsonSerializable
{
    use BaseModel;

    /** Liability has shifted to the card issuer. Available only after order is authorized or captured. */
    public const LIABILITY_SHIFT_YES = 'YES';

    /** Liability is with the merchant. */
    public const LIABILITY_SHIFT_NO = 'NO';

    /** Liability may shift to the card issuer. Available only before order is authorized or captured. */
    public const LIABILITY_SHIFT_POSSIBLE = 'POSSIBLE';

    /** The authentication system is not available. */
    public const LIABILITY_SHIFT_UNKNOWN = 'UNKNOWN';

    /**
     * Liability shift indicator. The outcome of the issuer's authentication.
     *
     * use one of constants defined in this class to set the value:
     * @see LIABILITY_SHIFT_YES
     * @see LIABILITY_SHIFT_NO
     * @see LIABILITY_SHIFT_POSSIBLE
     * @see LIABILITY_SHIFT_UNKNOWN
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $liability_shift;

    /**
     * Results of 3D Secure Authentication.
     *
     * @var ThreeDSecureAuthenticationResponse | null
     */
    public $three_d_secure;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->liability_shift) || Assert::minLength(
            $this->liability_shift,
            1,
            "liability_shift in AuthenticationResponse must have minlength of 1 $within"
        );
        !isset($this->liability_shift) || Assert::maxLength(
            $this->liability_shift,
            255,
            "liability_shift in AuthenticationResponse must have maxlength of 255 $within"
        );
        !isset($this->three_d_secure) || Assert::isInstanceOf(
            $this->three_d_secure,
            ThreeDSecureAuthenticationResponse::class,
            "three_d_secure in AuthenticationResponse must be instance of ThreeDSecureAuthenticationResponse $within"
        );
        !isset($this->three_d_secure) ||  $this->three_d_secure->validate(AuthenticationResponse::class);
    }

    private function map(array $data)
    {
        if (isset($data['liability_shift'])) {
            $this->liability_shift = $data['liability_shift'];
        }
        if (isset($data['three_d_secure'])) {
            $this->three_d_secure = new ThreeDSecureAuthenticationResponse($data['three_d_secure']);
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initThreeDSecure(): ThreeDSecureAuthenticationResponse
    {
        return $this->three_d_secure = new ThreeDSecureAuthenticationResponse();
    }
}
