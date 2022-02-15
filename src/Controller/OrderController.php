<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;

/**
 * Class OrderController
 * @package OxidSolutionCatalysts\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;

    public function init()
    {
        $this->processSubscriptionFunctionality();
        parent::init();
    }

    public function render()
    {
        $this->processSubscriptionFunctionality();

        if (Registry::getSession()->getVariable('oscpaypal_payment_redirect')) {
            Registry::getSession()->deleteVariable('oscpaypal_payment_redirect');
            Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl() . 'cl=user', true, 302);
        }

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

    public function finalizeuapm(): string
    {
        $uapmRequestId = (string) Registry::getRequest()->getRequestParameter('token');
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionUapmOrderId = PayPalSession::getUapmCheckoutOrderId();

        if (!$sessionOrderId || !$sessionUapmOrderId || ($uapmRequestId !== $uapmRequestId)) {
            $this->canceluapm('request to session mismatch');
        }

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterUapmRedirect($sessionUapmOrderId);

        } catch (\Exception $exception) {
            $this->canceluapm('cannot finalize order');
        }

        return 'thankyou';
    }

    public function canceluapm(string $errorcode = null): string
    {
        //TODO: we get the PayPal order id retuned in token parameter, can be used for paranoia checks
        //(string) Registry::getRequest()->getRequestParameter('token')
        $requestErrorcode = (string) Registry::getRequest()->getRequestParameter('errorcode');

        $this->getServiceFromContainer(PaymentService::class)
            ->removeTemporaryOrder();

        $goNext = 'payment';
        if (PayPalSession::getUapmSessionError() || $errorcode || $requestErrorcode) {
            PayPalSession::unsetUapmSessionError();
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
    }

    protected function _getNextStep($success) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_UAPMINPROGRESSS == $success) &&
            ($redirectLink = PayPalSession::getUapmRedirectLink())
        ) {
            PayPalSession::unsetUapmRedirectLink();
            throw new Redirect($redirectLink);
        }

        return parent::_getNextStep($success);
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
