<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation;
/**
 * Implements the recommended actions according to
 * PayPal documentation: https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
 *
 * This class handles the 3D Secure authentication flow and determines whether a payment
 * should be authorized based on the card's enrollment status, authentication status,
 * and liability shift indicators.
 */
class SCAValidator implements SCAValidatorInterface
{

    /** @var ModuleSettings */
    private $moduleSettingsService;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        ServiceFactory $serviceFactory,
        ModuleSettings $moduleSettingsService
    )
    {
        $this->serviceFactory = $serviceFactory;
        $this->moduleSettingsService = $moduleSettingsService;
    }

    public function verify3D(string $paymentId, ?PayPalApiOrder $payPalOrder = null): bool
    {
        // Check 3DS eligibility via SCA validator (payment method specific)
        // If not eligible (e.g., non-card payments or SCA explicitly ignored), allow payment
        if (!$this->isEligibleFor3DS($paymentId)) {
            return true;
        }

        try {
            // If no PayPal order is provided, attempt to load it using the checkout order id from the session
            if ($payPalOrder === null) {
                $checkoutOrderId = PayPalSession::getCheckoutOrderId();
                // If there is no current order in session, the verification cannot be performed
                if (empty($checkoutOrderId)) {
                    return false;
                }

                $payPalOrder = $this->serviceFactory->getOrderService()
                    ->showOrderDetails(
                        $checkoutOrderId,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
            }

            $authenticationResponse = $this->getCardAuthenticationResponse($payPalOrder);
        } catch (CardValidation|ApiException $e) {
            // Errors during verification: reject verification
            return false;
        }

        try {
            switch ($this->moduleSettingsService->getPayPalSCAContingency()) {
                case Constants::PAYPAL_SCA_WHEN_REQUIRED:
                    return is_null($authenticationResponse) || $this->isCardUsableForPayment($payPalOrder, $authenticationResponse);

                case Constants::PAYPAL_SCA_ALWAYS:
                    return $this->isCardUsableForPayment($payPalOrder, $authenticationResponse);
            }
        } catch (CardValidation $e) {
            // Errors during verification: reject verification
            return false;
        }

        return false;
    }

    /**
     * Checks whether the given payment method should use 3D Secure flow.
     */
    public function isEligibleFor3DS(string $paymentId): bool
    {
        //3ds disabled in module settings
        if ($this->getModuleSettingsService()->alwaysIgnoreSCAResult()) {
            return false;
        }

        return in_array($paymentId, [
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID,
        ], true);
    }

    /**
     * Liability shift values:
     * - POSSIBLE: Liability has shifted to the card issuer.
     * - YES: Liability has shifted to the card issuer.
     * - NO: Liability remains with the merchant.
     */
    public const LIABILITY_SHIFT_POSSIBLE = 'POSSIBLE';
    public const LIABILITY_SHIFT_YES = 'YES';
    public const LIABILITY_SHIFT_NO = 'NO';

    /**
     * Enrollment status values:
     * - Y: Card is enrolled in 3D Secure program.
     * - N: Card is not enrolled in 3D Secure program.
     * - U: Unable to determine if card is enrolled.
     * - B: Card is enrolled but authentication bypassed.
     */
    public const ENROLLMENT_STATUS_YES     = 'Y';
    public const ENROLLMENT_STATUS_NO      = 'N';
    public const ENROLLMENT_STATUS_UNKNOWN = 'U';
    public const ENROLLMENT_STATUS_BYPASS  = 'B';

    /**
     * Authentication status values:
     * - Y: Authentication successful, full authentication.
     * - N: Authentication failed, customer failed or canceled authentication.
     * - R: Authentication rejected, authentication rejected by the issuer.
     * - A: Authentication attempted but not completed, proof of attempt generated.
     * - U: Authentication unavailable, issuer unable to perform authentication.
     * - C: Challenge required for authentication, additional authentication required.
     */
    public const AUTH_STATUS_SUCCESS   = 'Y';
    public const AUTH_STATUS_FAILED    = 'N';
    public const AUTH_STATUS_REJECTED  = 'R';
    public const AUTH_STATUS_ATTEMPTED = 'A';
    public const AUTH_STATUS_UNAVAILABLE = 'U';
    public const AUTH_STATUS_CHALLENGE = 'C';

    /**
     * Determines if a card is usable for payment based on 3D Secure authentication results.
     *
     * This method implements PayPal's recommended actions for handling 3D Secure authentication:
     * 1. If no authentication result is available, allow the payment to proceed
     * 2. For cards not enrolled in 3D Secure, allow the payment to proceed
     * 3. For enrolled cards, base the decision on authentication status and liability shift
     *    - Successful authentication with liability shift: Allow payment
     *    - Attempted authentication with POSSIBLE liability shift: Allow payment
     *    - Failed authentication or no liability shift: Decline payment
     *
     * @param \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $order
     * @param \OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse|null $authenticationResponse
     * @return bool True if the card should be allowed for payment, false otherwise
     * @throws CardValidation If payment source information is missing or invalid
     */
    public function isCardUsableForPayment(
        PayPalApiOrder $order,
        ?AuthenticationResponse $authenticationResponse = null
    ): bool
    {
        $authenticationResponse = $authenticationResponse ??
            $this->getCardAuthenticationResponse($order);

        // According to PayPal docs, if there's no authentication result, we should allow the payment
        // This follows PayPal's recommendation for cases where 3D Secure verification wasn't performed
        if (is_null($authenticationResponse)) {
            return true;
        }

        // Extract enrollment status, defaulting to empty string if not available
        // Enrollment status indicates whether the card is enrolled in the 3D Secure program
        $enrollmentStatus = !is_null($authenticationResponse->three_d_secure) &&
            !is_null($authenticationResponse->three_d_secure->enrollment_status) ?
            (string) $authenticationResponse->three_d_secure->enrollment_status : '';

        // Extract authentication status, defaulting to empty string if not available
        // Authentication status indicates the result of the 3D Secure authentication attempt
        $authStatus = !is_null($authenticationResponse->three_d_secure) &&
             !is_null($authenticationResponse->three_d_secure->authentication_status) ?
            (string) $authenticationResponse->three_d_secure->authentication_status : '';

        // Extract liability shift, defaulting to empty string if not available
        // Liability shift indicates whether the liability for fraud has shifted from the merchant to the card issuer
        $liabilityShift = !is_null($authenticationResponse->liability_shift) ?
            (string) $authenticationResponse->liability_shift : '';

        // Delegate to shouldContinueAuthorization to apply PayPal's recommended actions
        return $this->shouldContinueAuthorization($enrollmentStatus, $authStatus, $liabilityShift);
    }

    /**
     * Extracts the 3D Secure authentication result from a PayPal order.
     *
     * This method safely retrieves the authentication result from the PayPal order object,
     * performing necessary null checks to prevent errors. According to PayPal's documentation,
     * the authentication result contains critical information needed to determine whether
     * to proceed with payment authorization:
     * - Enrollment status (whether the card is enrolled in 3D Secure)
     * - Authentication status (the result of the authentication attempt)
     * - Liability shift indicator (whether liability has shifted to the card issuer)
     *
     * @param PayPalApiOrder $payPalOrder The PayPal order to extract authentication results from
     * @return AuthenticationResponse|null The authentication result, or null if not available
     * @throws CardValidation If payment source information is missing or invalid
     */
    public function getCardAuthenticationResponse(PayPalApiOrder $payPalOrder): ?AuthenticationResponse
    {
        // Verify payment source exists
        if (is_null($payPalOrder->payment_source)) {
            throw CardValidation::byMissingPaymentSource();
        }

        // Verify card payment source exists
        if (is_null($payPalOrder->payment_source->card) && is_null($payPalOrder->payment_source->google_pay)) {
            throw CardValidation::byPaymentSource();
        }

        // If no authentication result is available, return null
        // According to PayPal docs, this is a valid scenario and should allow payment to proceed
        if (
            is_null($payPalOrder->payment_source->card->authentication_result) &&
            is_null($payPalOrder->payment_source->google_pay->card->authentication_result)
        ) {
            return null;
        }

        return $payPalOrder->payment_source->card->authentication_result
            ?? ($payPalOrder->payment_source->google_pay->card->authentication_result ?? null);
    }

    /**
     * Determines whether to continue with authorization based on enrollment status, authentication status,
     * and liability shift indicators.
     *
     * PayPal recommended actions:
     * - For cards not enrolled in 3D Secure (N, U, B): Continue with authorization
     * - For enrolled cards (Y): Decision depends on authentication status and liability shift
     *
     * @param string $enrollmentStatus The 3D Secure enrollment status (Y, N, U, B)
     * @param string $authStatus The authentication status (Y, N, R, A, U, C)
     * @param string $liabilityShift Whether liability has shifted to the card issuer (POSSIBLE, YES, NO)
     * @return bool True if authorization should continue, false otherwise
     */
    protected function shouldContinueAuthorization(
        string $enrollmentStatus,
        string $authStatus,
        string $liabilityShift
    ): bool {
        // Normalize inputs to uppercase
        $enrollmentStatus = strtoupper($enrollmentStatus);
        $authStatus = strtoupper($authStatus);
        $liabilityShift = strtoupper($liabilityShift);

        // If enrollment status is empty, treat as not enrolled
        // PayPal recommends continuing with authorization for non-enrolled cards
        if (empty($enrollmentStatus)) {
            return true;
        }

        switch ($enrollmentStatus) {
            case self::ENROLLMENT_STATUS_YES:
                // Card is enrolled in 3D Secure program
                // For enrolled cards, the decision depends on authentication status and liability shift
                return $this->handleEnrolledCase($authStatus, $liabilityShift);

            case self::ENROLLMENT_STATUS_NO:
                // Card is not enrolled in 3D Secure program
                // PayPal recommends continuing with authorization
                return true;

            case self::ENROLLMENT_STATUS_UNKNOWN:
                // Unable to determine if card is enrolled
                // PayPal recommends continuing with authorization
                return true;

            case self::ENROLLMENT_STATUS_BYPASS:
                // Card is enrolled but authentication bypassed
                // PayPal recommends continuing with authorization
                return true;

            default:
                // Unknown enrollment status - safest to decline
                return false;
        }
    }

    /**
     * Handles the authorization decision for cards enrolled in 3D Secure.
     *
     * PayPal recommended actions for enrolled cards:
     * - Authentication Success (Y):
     *   - If liability shift is POSSIBLE or YES: Continue with authorization
     *   - If liability shift is NO: Do not continue with authorization
     * - Authentication Attempted (A):
     *   - If liability shift is POSSIBLE: Continue with authorization
     *   - If liability shift is NO or YES: Do not continue with authorization
     * - Authentication Failed (N): Do not continue with authorization
     * - Authentication Rejected (R): Do not continue with authorization
     * - Authentication Unavailable (U): Do not continue with authorization
     * - Challenge Required (C): Do not continue with authorization
     *
     * @param string $authStatus The authentication status (Y, N, R, A, U, C)
     * @param string $liabilityShift Whether liability has shifted to the card issuer (POSSIBLE, YES, NO)
     * @return bool True if authorization should continue, false otherwise
     */
    private function handleEnrolledCase(string $authStatus, string $liabilityShift): bool
    {
        // If authentication status is empty, we should not continue
        // PayPal recommends not proceeding when authentication status is missing
        if (empty($authStatus)) {
            return false;
        }

        switch ($authStatus) {
            case self::AUTH_STATUS_SUCCESS:
                // Authentication successful (Y)
                // PayPal recommends continuing only if liability has shifted
                return $liabilityShift === self::LIABILITY_SHIFT_POSSIBLE ||
                    $liabilityShift === self::LIABILITY_SHIFT_YES;

            case self::AUTH_STATUS_ATTEMPTED:
                // Authentication attempted but not completed (A)
                // PayPal recommends continuing only if liability shift is POSSIBLE
                return $liabilityShift === self::LIABILITY_SHIFT_POSSIBLE;

            case self::AUTH_STATUS_FAILED:
                // Authentication failed (N)
                // PayPal recommends not continuing with authorization
                return false;

            case self::AUTH_STATUS_REJECTED:
                // Authentication rejected by issuer (R)
                // PayPal recommends not continuing with authorization
                return false;

            case self::AUTH_STATUS_UNAVAILABLE:
                // Authentication unavailable (U)
                // PayPal recommends not continuing with authorization
                return false;

            case self::AUTH_STATUS_CHALLENGE:
                // Challenge required for authentication (C)
                // PayPal recommends not continuing with authorization
                return false;

            default:
                // For any other authentication status, do not continue
                // Safest approach for unknown authentication status
                return false;
        }
    }

    /**
     * @return \OxidSolutionCatalysts\PayPal\Service\ModuleSettings
     */
    public function getModuleSettingsService(): ModuleSettings
    {
        return $this->moduleSettingsService;
    }
}
