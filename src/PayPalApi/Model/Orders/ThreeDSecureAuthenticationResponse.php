<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * Results of 3D Secure Authentication.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-three_d_secure_authentication_response.json
 */
class ThreeDSecureAuthenticationResponse implements JsonSerializable
{
    use BaseModel;

    /** Successful authentication. */
    public const AUTHENTICATION_STATUS_Y = 'Y';

    /** Failed authentication / account not verified / transaction denied. */
    public const AUTHENTICATION_STATUS_N = 'N';

    /** Unable to complete authentication. */
    public const AUTHENTICATION_STATUS_U = 'U';

    /** Successful attempts transaction. */
    public const AUTHENTICATION_STATUS_A = 'A';

    /** Challenge required for authentication. */
    public const AUTHENTICATION_STATUS_C = 'C';

    /** Authentication rejected (merchant must not submit for authorization). */
    public const AUTHENTICATION_STATUS_R = 'R';

    /** Challenge required; decoupled authentication confirmed. */
    public const AUTHENTICATION_STATUS_D = 'D';

    /** Informational only; 3DS requestor challenge preference acknowledged. */
    public const AUTHENTICATION_STATUS_I = 'I';

    /** Yes. The bank is participating in 3-D Secure protocol and will return the ACSUrl. */
    public const ENROLLMENT_STATUS_Y = 'Y';

    /** No. The bank is not participating in 3-D Secure protocol. */
    public const ENROLLMENT_STATUS_N = 'N';

    /** Unavailable. The DS or ACS is not available for authentication at the time of the request. */
    public const ENROLLMENT_STATUS_U = 'U';

    /** Bypass. The merchant authentication rule is triggered to bypass authentication. */
    public const ENROLLMENT_STATUS_B = 'B';

    /**
     * Transactions status result identifier. The outcome of the issuer's authentication.
     *
     * use one of constants defined in this class to set the value:
     * @see AUTHENTICATION_STATUS_Y
     * @see AUTHENTICATION_STATUS_N
     * @see AUTHENTICATION_STATUS_U
     * @see AUTHENTICATION_STATUS_A
     * @see AUTHENTICATION_STATUS_C
     * @see AUTHENTICATION_STATUS_R
     * @see AUTHENTICATION_STATUS_D
     * @see AUTHENTICATION_STATUS_I
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $authentication_status;

    /**
     * Status of Authentication eligibility.
     *
     * use one of constants defined in this class to set the value:
     * @see ENROLLMENT_STATUS_Y
     * @see ENROLLMENT_STATUS_N
     * @see ENROLLMENT_STATUS_U
     * @see ENROLLMENT_STATUS_B
     * @var string | null
     * minLength: 1
     * maxLength: 255
     */
    public $enrollment_status;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->authentication_status) || Assert::minLength(
            $this->authentication_status,
            1,
            "authentication_status in ThreeDSecureAuthenticationResponse must have minlength of 1 $within"
        );
        !isset($this->authentication_status) || Assert::maxLength(
            $this->authentication_status,
            255,
            "authentication_status in ThreeDSecureAuthenticationResponse must have maxlength of 255 $within"
        );
        !isset($this->enrollment_status) || Assert::minLength(
            $this->enrollment_status,
            1,
            "enrollment_status in ThreeDSecureAuthenticationResponse must have minlength of 1 $within"
        );
        !isset($this->enrollment_status) || Assert::maxLength(
            $this->enrollment_status,
            255,
            "enrollment_status in ThreeDSecureAuthenticationResponse must have maxlength of 255 $within"
        );
    }

    private function map(array $data)
    {
        if (isset($data['authentication_status'])) {
            $this->authentication_status = $data['authentication_status'];
        }
        if (isset($data['enrollment_status'])) {
            $this->enrollment_status = $data['enrollment_status'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
