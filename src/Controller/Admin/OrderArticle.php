<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

/**
* OrderArticle class
*
* @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderArticle
*/
class OrderArticle extends OrderArticle_parent
{
    public function render()
    {
        $parent = parent::render();
        if ($order = $this->getEditObject()) {
            if (
                $order->paidWithPayPal() ||
                 $order->paidWithPayPalPlus() ||
                 $order->paidWithPayPalSoap()
            ) {
                $capture = $order->getOrderPaymentCapture();
                if (!is_null($capture)) {
                    $this->_aViewData["readonly"] = true;
                }
            }
        }
        return $parent;
    }
}
