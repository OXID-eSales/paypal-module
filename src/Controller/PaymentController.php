<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

class PaymentController extends PaymentController_parent
{
    use ServiceContainer;

    /**
     * Template variable getter. Returns paymentlist
     *
     * @return array<array-key, mixed>|object
     */
    public function getPaymentList()
    {
        $paymentList = parent::getPaymentList();

        // check if basic config exists
        if (!$this->getServiceFromContainer(ModuleSettings::class)->checkHealth()) {
            $paymentListRaw = $paymentList;
            $paymentList = [];

            foreach ($paymentListRaw as $key => $payment) {
                if (strpos($key, 'oscpaypal') !== false) {
                    continue;
                }
                $paymentList[$key] = $payment;
            }
        }

        // check ACDC Eligibility
        if (!$this->getServiceFromContainer(ModuleSettings::class)->isAcdcEligibility()) {
            unset ($paymentList[PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID]);
        }

        // check Pui Eligibility
        if (!$this->getServiceFromContainer(ModuleSettings::class)->isPayPalPuiEligibility()) {
            unset ($paymentList[PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID]);
        }

        return $paymentList;
    }
}
