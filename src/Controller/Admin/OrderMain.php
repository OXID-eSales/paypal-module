<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidSolutionCatalysts\PayPal\Traits\AdminOrderTrait;

/**
* OrderMain class
*
* @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderMain
*/
class OrderMain extends OrderMain_parent
{
    use AdminOrderTrait;

    protected function onOrderSend()
    {
        parent::onOrderSend();
        if ($this->isPayPalStandardOnDeliveryCapture()) {
            $this->capturePayPalStandard();
        }
    }
}
