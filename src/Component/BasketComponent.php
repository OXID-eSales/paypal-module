<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\BasketComponent
 */
class BasketComponent extends BasketComponent_parent
{
    use ServiceContainer;

    /**
     * Initiates component.
     */
    public function init()
    {
        parent::init();

        $this->checkForAbortedPayPalOrderPlacement();
    }

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

    /**
     * Check if the customer has placed an order but has aborted the cl=thankyou redirect.
     * In this case, the order is finished and paid.
     */
    protected function checkForAbortedPayPalOrderPlacement(): void
    {
        $session = Registry::getSession();
        $basket = $session->getBasket();

        if ($basket === null) {
            return;
        }

        $orderId = $basket->getOrderId();
        if (empty($orderId)) {
            return;
        }

        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = oxNew(Order::class);
        $order->load($orderId);
        if (
            $order->isOrderSuccessfullyPaid()
        ) {
            // Delete the session variable that would prevent an order confirmation email from being sent.
            $session->deleteVariable('isPayPalPaymentCheckout');
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->info('Found aborted order placement, redirecting to thankyou page.');
            Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl() . 'cl=thankyou');
        }
    }
}
