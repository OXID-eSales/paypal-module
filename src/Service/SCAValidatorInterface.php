<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;

/**
 * Recommended actions according to
 * PayPal recomendations https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
 */
interface SCAValidatorInterface
{
    /**
     * Checks if the given payment method is eligible for 3D Secure processing.
     */
    public function isEligibleFor3DS(string $paymentId): bool;

    public function isCardUsableForPayment(
        PayPalApiOrder $order,
        ?AuthenticationResponse $authenticationResponse = null
    ): bool;

    public function getCardAuthenticationResponse(PayPalApiOrder $payPalOrder): ?AuthenticationResponse;

    public function getModuleSettingsService(): ModuleSettings;
    public function verify3D(string $paymentId, ?PayPalApiOrder $payPalOrder = null): bool;
}
