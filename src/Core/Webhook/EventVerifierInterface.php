<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidSolutionCatalysts\PayPal\Core\RequestReader;

/**
 * Interface for webhook event verification services
 */
interface EventVerifierInterface
{
    /**
     * Verify a webhook event
     *
     * @param RequestReader $requestReader The request reader containing webhook data
     * @return bool True if the webhook is verified, false otherwise
     * @throws \Exception If verification fails due to an error
     */
    public function verify(RequestReader $requestReader): bool;
}