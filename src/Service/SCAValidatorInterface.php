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
interface SCAValidatorInterface
{
    public function isCardUsableForPayment(PayPalApiOrder $order): bool;

    public function getCardAuthenticationResult(PayPalApiOrder $order): ?AuthenticationResponse;
}
