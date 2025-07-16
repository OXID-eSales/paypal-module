<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation;

/**
 * Recommended actions according to
 * PayPal recommendations https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
 */
class SCAValidator implements SCAValidatorInterface
{
    public const LIABILITY_SHIFT_POSSIBLE = 'POSSIBLE';
    public const LIABILITY_SHIFT_YES = 'YES';
    public const LIABILITY_SHIFT_NO = 'NO';
    public const ENROLLMENT_STATUS_YES     = 'Y';
    public const ENROLLMENT_STATUS_NO      = 'N';
    public const ENROLLMENT_STATUS_UNKNOWN = 'U';
    public const ENROLLMENT_STATUS_BYPASS  = 'B';

    public const AUTH_STATUS_SUCCESS   = 'Y';
    public const AUTH_STATUS_FAILED    = 'N';
    public const AUTH_STATUS_REJECTED  = 'R';
    public const AUTH_STATUS_ATTEMPTED = 'A';
    public const AUTH_STATUS_UNAVAILABLE = 'U';
    public const AUTH_STATUS_CHALLENGE = 'C';

    /**
     * @throws CardValidation
     */
    public function isCardUsableForPayment(PayPalApiOrder $order): bool
    {
        $authenticationResult = $this->getCardAuthenticationResult($order);
        if (is_null($authenticationResult)) {
            return false;
        }

        $enrollmentStatus = !is_null($authenticationResult->three_d_secure) &&
            !is_null($authenticationResult->three_d_secure->enrollment_status) ?
            (string) $authenticationResult->three_d_secure->enrollment_status : '';

        $authStatus = !is_null($authenticationResult->three_d_secure) &&
             !is_null($authenticationResult->three_d_secure->authentication_status) ?
            (string) $authenticationResult->three_d_secure->authentication_status : '';

        $liabilityShift = (string) $authenticationResult->liability_shift;

        return $this->shouldContinueAuthorization($enrollmentStatus, $authStatus, $liabilityShift);
    }

    public function getCardAuthenticationResult(PayPalApiOrder $order): ?AuthenticationResponse
    {
        if (is_null($order->payment_source)) {
            throw CardValidation::byMissingPaymentSource();
        }

        if (is_null($order->payment_source->card)) {
            throw CardValidation::byPaymentSource();
        }

        if (is_null($order->payment_source->card->authentication_result)) {
            return null;
        }

        return $order->payment_source->card->authentication_result;
    }

    /**
     * Determines whether to continue with authorization based on enrollment and authentication status
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

        switch ($enrollmentStatus) {
            case self::ENROLLMENT_STATUS_YES:
                return $this->handleEnrolledCase($authStatus, $liabilityShift);

            case self::ENROLLMENT_STATUS_NO:
            case self::ENROLLMENT_STATUS_UNKNOWN:
            case self::ENROLLMENT_STATUS_BYPASS:
                return $liabilityShift === self::LIABILITY_SHIFT_NO;

            default:
                return false;
        }
    }

    private function handleEnrolledCase(string $authStatus, string $liabilityShift): bool
    {
        switch ($authStatus) {
            case self::AUTH_STATUS_SUCCESS:
                return $liabilityShift === self::LIABILITY_SHIFT_POSSIBLE ||
                    $liabilityShift === self::LIABILITY_SHIFT_YES;

            case self::AUTH_STATUS_ATTEMPTED:
                return $liabilityShift === self::LIABILITY_SHIFT_POSSIBLE;

            case self::AUTH_STATUS_FAILED:
            case self::AUTH_STATUS_REJECTED:
            case self::AUTH_STATUS_UNAVAILABLE:
            case self::AUTH_STATUS_CHALLENGE:
                return false;
        }

        return false;
    }
}