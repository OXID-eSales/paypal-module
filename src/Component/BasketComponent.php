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
        $this->resetExpressOrderAndShowError();
        return parent::changeBasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
    }

    /**
     * @param $sProductId
     * @param $dAmount
     * @param $aSel
     * @param $aPersParam
     * @param $blOverride
     * @return mixed
     */
    public function toBasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        $this->resetExpressOrderAndShowError();
        return parent::toBasket($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
    }

    /**
     *  Resets the session if the user has previously authorized a payment
     *  with PayPal Express and is now adding or changing an item to the basket.
     * @return void
     */
    protected function resetExpressOrderAndShowError()
    {
        if (PayPalSession::isPayPalExpressOrderActive()) {
            PayPalSession::unsetPayPalOrderId();
            Registry::getSession()->getBasket()->setPayment(null);
            Registry::getUtilsView()->addErrorToDisplay('OSCPAYPAL_KILL_EXPRESS_SESSION_REASON');
        }
    }
}
