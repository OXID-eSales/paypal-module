<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

/**
 * Class OrderController
 * @package OxidSolutionCatalysts\PayPal\Controller
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
            $sql = 'UPDATE osc_paypal_subscription SET OXORDERID = ? WHERE OXID = ?';
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
