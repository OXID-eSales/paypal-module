<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;

class OrderProcessTrackingService
{
    private string $trackingId = '';

    public function getTrackingId(): string
    {
        return (string)(!empty($this->trackingId)
            ? $this->trackingId : Registry::getSession()->getVariable('payPalPaymentProcessId'));
    }

    public function setTrackingId(string $trackingId): void
    {
        $this->trackingId = $trackingId;
    }

    public function startPaymentProcessTracking()
    {
        $this->trackingId = bin2hex(random_bytes(3));
        Registry::getSession()->setVariable('payPalPaymentProcessId', $this->trackingId);
    }

    public function finishPaymentProcessTracking()
    {
        Registry::getSession()->deleteVariable('payPalPaymentProcessId');
        $this->trackingId = '';
    }

    public function isPaymentProcessStarted(): bool
    {
        return !empty($this->trackingId) || !empty(Registry::getSession()->getVariable('payPalPaymentProcessId'));
    }
}
