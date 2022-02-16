<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidSolutionCatalysts\PayPal\Core\Config;

class PaymentController extends PaymentController_parent
{
    /**
     * Template variable getter. Returns paymentlist
     *
     * @return array<array-key, mixed>|object
     */
    public function getPaymentList()
    {
        $paymentList = parent::getPaymentList();

        // check if basic config exists
        if (!Config::checkHealth()) {
            $paymentListRaw = $paymentList;
            $paymentList = [];

            foreach ($paymentListRaw as $key => $payment) {
                if (strpos($key, 'oxidpaypal_') !== false) {
                    continue;
                }
                $paymentList[$key] = $payment;
            }
        }

        return $paymentList;
    }
}
