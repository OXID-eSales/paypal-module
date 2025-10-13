<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderTrait;

/**
* OrderMain class
*
* @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderOverview
*/
class OrderOverview extends OrderOverview_parent
{
    use AdminOrderTrait;

    /**
     * Sends order.
     */
    public function sendorder()
    {
        parent::sendorder();

        $paymentId = $this->getOrder()->getPayment()->getId();

        if (PayPalDefinitions::isPayPalPayment($paymentId) && $this->isPayPalOrderCaptureOnDelivery()) {
            $this->capturePayPalOrder();
        }
    }
}
