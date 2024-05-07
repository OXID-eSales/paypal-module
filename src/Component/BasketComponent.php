<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\BasketComponent
 */
class BasketComponent extends BasketComponent_parent
{
    /**
     * Resets the session if the user has previously authorized a payment
     * with PayPal Express and is now changing their basket.
     *
     * @param $sProductId
     * @param $dAmount
     * @param $aSel
     * @param $aPersParam
     * @param $blOverride
     * @return mixed
     */
    public function changeBasket(
        $sProductId = null,
        $dAmount = null,
        $aSel = null,
        $aPersParam = null,
        $blOverride = true
    ) {

        if (PayPalSession::isPayPalExpressOrderActive()) {
            PayPalSession::unsetPayPalOrderId();
            Registry::getSession()->getBasket()->setPayment(null);
        }
        return parent::changeBasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
    }

    /**
     * Resets the session if the user has previously authorized a payment
     *  with PayPal Express and is now adding an item to the basket.
     *
     * @param $sProductId
     * @param $dAmount
     * @param $aSel
     * @param $aPersParam
     * @param $blOverride
     * @return mixed
     */
    public function toBasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        if (PayPalSession::isPayPalExpressOrderActive()) {
            PayPalSession::unsetPayPalOrderId();
            Registry::getSession()->getBasket()->setPayment(null);
        }
        return parent::toBasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
    }
}
