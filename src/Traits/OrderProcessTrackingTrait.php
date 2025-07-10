<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\src\Traits;

use OxidEsales\Eshop\Core\Registry;

trait OrderProcessTrackingTrait
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
        $this->trackingId = substr(md5(uniqid()), 0, 6);
        Registry::getSession()->setVariable('payPalPaymentProcessId', $this->trackingId);
    }

    public function finishPaymentProcessTracking()
    {
        Registry::getSession()->deleteVariable('payPalPaymentProcessId');
        $this->trackingId = '';
    }
}
