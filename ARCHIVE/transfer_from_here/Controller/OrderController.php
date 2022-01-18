<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidProfessionalServices\PayPal\Core\PayPalSession;

/**
 * Class OrderController
 * @package OxidProfessionalServices\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    public function init()
    {
        $this->processSubscriptionFunctionality();
        parent::init();
    }

    public function render()
    {
        $this->processSubscriptionFunctionality();
        return parent::render();
    }

    public function execute()
    {
        $ret = parent::execute();

        if (strpos($ret, 'thankyou') === false) {
            return $ret;
        }

        $session = $this->getSession();
        $oBasket =  $session->getBasket();

        if ($oBasket->getPaymentId() !== 'oxidpaypal') {
            return $ret;
        }

        // save order id to subscription
        if ($subscriptionProductOrderId = $session->getVariable('subscriptionProductOrderId')) {
            $orderId = Registry::getSession()->getVariable('sess_challenge');
            $sql = 'UPDATE oxps_paypal_subscription SET OXORDERID = ? WHERE OXID = ?';
            DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->execute(
                $sql,
                [
                    $orderId,
                    $subscriptionProductOrderId
                ]
            );
        }

        PayPalSession::subscriptionIsDoneProcessing();

        return $ret;
    }

    private function setPayPalAsPaymentMethod()
    {
        $payment = $this->getBasket()->getPaymentId();
        if (($payment !== 'oxidpaypal')) {
            $this->getBasket()->setPayment('oxidpaypal');
        }
    }

    private function processSubscriptionFunctionality(): void
    {
        $isSubscribe = Registry::getRequest()->getRequestEscapedParameter('subscribe', 0);
        $showOverlay = Registry::getRequest()->getRequestEscapedParameter('showOverlay', 0);

        if ($showOverlay) {
            $this->addTplParam('loadingScreen', 'true');
        }

        if ($isSubscribe) {
            $func = Registry::getRequest()->getRequestEscapedParameter('func');

            if ($func === 'doOrder') {
                $this->addTplParam('submitCart', 1);
                $session = $this->getSession();
                $session->setVariable('isSubscriptionCheckout', true);
            }
            $this->setPayPalAsPaymentMethod();
        }
    }
}
