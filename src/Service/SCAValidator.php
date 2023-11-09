<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation;

/**
 * Recommended actions according to
 * PayPal recomendations https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
 */
class SCAValidator implements SCAValidatorInterface
{
    public const LIABILITY_SHIFT_POSSIBLE = 'POSSIBLE';
    public const LIABILITY_SHIFT_NO = 'NO';
    public const LIABILITY_SHIFT_UNKNOWN = 'UNKNOWN';

    public const ENROLLMENT_STATUS_YES     = 'Y';
    public const ENROLLMENT_STATUS_NO      = 'N';
    public const ENROLLMENT_STATUS_UNKNOWN = 'U';
    public const ENROLLMENT_STATUS_BYPASS  = 'B';

    public const AUTH_STATUS_SUCCESS   = 'Y';
    public const AUTH_STATUS_FAILED    = 'N';
    public const AUTH_STATUS_REJECTED  = 'R';
    public const AUTH_STATUS_ATTEMPTED = 'A';

    private $okToProceed = [
        [
            'enroll' => self::ENROLLMENT_STATUS_YES,
            'liability' => self::LIABILITY_SHIFT_POSSIBLE,
            'auth' => self::AUTH_STATUS_SUCCESS
        ],
        [
            'enroll' => self::ENROLLMENT_STATUS_YES,
            'liability' => self::LIABILITY_SHIFT_POSSIBLE,
            'auth' => self::AUTH_STATUS_ATTEMPTED,
        ],
        [
            'enroll' => self::ENROLLMENT_STATUS_NO,
            'liability' => self::LIABILITY_SHIFT_NO
        ],
        [
            'enroll' => self::ENROLLMENT_STATUS_UNKNOWN,
            'liability' => self::LIABILITY_SHIFT_NO
        ],
        [
            'enroll' => self::ENROLLMENT_STATUS_BYPASS,
            'liability' => self::LIABILITY_SHIFT_NO
        ],
    ];

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

        $combi = [
            'enroll' => $enrollmentStatus,
            'liability' => $liabilityShift
        ];
        if ($authStatus) {
            $combi['auth'] = $authStatus;
        }

        $isOk = in_array($combi, $this->okToProceed) ? true : false;

        return $isOk;
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
}
