<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

/**
 * @mixin \OxidEsales\Eshop\Application\Controller\BasketController
 */
class BasketController extends BasketController_parent
{
    public function render()
    {
        $selectedBillingCycle = Registry::getSession()->getVariable('selectedBillingCycle');

        $this->addTplParam('selectedBillingCycle', []);

        if (PayPalSession::isSubscriptionProcessing()) {
            $this->addTplParam('loadingScreen', true);
        }

        if (!empty($selectedBillingCycle)) {
            $selectedBillingCycle = json_decode($selectedBillingCycle, true);
            $this->addTplParam('selectedBillingCycle', $selectedBillingCycle);
        }
        return parent::render();
    }
}
