<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalEshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Core\Exception\Redirect;

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

        $this->renderAcdcRetry();

        return parent::render();
    }

    protected function renderAcdcRetry()
    {
        if (Registry::getRequest()->getRequestParameter('acdcretry')) {
            $displayError = oxNew(DisplayError::class);
            $displayError->setMessage('OSC_PAYPAL_ACDC_PLEASE_RETRY');
            Registry::getUtilsView()->addErrorToDisplay($displayError);

            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            if ((string) $paymentService->getSessionPaymentId() === PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID) {
                $paymentService->removeTemporaryOrder();
            }
        }
    }

    public function getUserCountryIso(): string
    {
        $result = '';
        if ($user = Registry::getSession()->getUser()) {
            $country = oxNew(Country::class);
            $country->load($user->getFieldData('oxcountryid'));
            $result = (string) $country->getFieldData('oxisoalpha2');
        }
        return $result;
    }

    public function getUserStateIso(): string
    {
        $result = '';
        if ($user = Registry::getSession()->getUser()) {
            $country = oxNew(State::class);
            $country->load($user->getFieldData('oxstateid'));
            $result = (string) $country->getFieldData('oxisoalpha2');
        }
        return $result;
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

    public function createAcdcOrder(): void
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $paymentService->removeTemporaryOrder();
        Registry::getSession()->setVariable('sess_challenge', $this->getUtilsObjectInstance()->generateUID());

        try {
            $status = $this->execute();
        } catch(\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            $this->outputJson(['acdcerror' => 'failed to execute shop order']);
            return;
        }

        $response = $paymentService->doCreateAcdcOrder(
            Registry::getSession()->getBasket()
        );
        if (!($paypalOrderId = $response['id'])) {
            $this->outputJson(['acdcerror' => 'cannot create paypal order']);
            return;
        }

        if (!$status || (PayPalEshopModelOrder::ORDER_STATE_ACDCINPROGRESS != $status)) {
            $response = ['acdcerror' => 'unexpected order status ' . $status];
            $paymentService->removeTemporaryOrder();
        } else {
            PayPalSession::storePayPalOrderId($paypalOrderId, Constants::SESSION_ACDCCHECKOUT_ORDER_ID);
            $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');
            $payPalOrder = $paymentService->getPayPalOrder($sessionOrderId, $paypalOrderId);
            $payPalOrder->setStatus($response['status']);
            $payPalOrder->save();
        }

        $this->outputJson($response);
    }

    public function captureAcdcOrder(): void
    {
        $acdcRequestId = (string) Registry::getRequest()->getRequestParameter('acdcorderid');
        $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');
        $sessionAcdcOrderId = (string) PayPalSession::getAcdcCheckoutOrderId();

        $result = [
            'details' => [
                'transaction failed'
            ],
            'location' => [
                'cl=payment&payerror=2'
            ]
        ];

        if (!$sessionOrderId || !$sessionAcdcOrderId || ($acdcRequestId !== $sessionAcdcOrderId)) {
            $this->getServiceFromContainer(PaymentService::class)->removeTemporaryOrder();
            $this->outputJson($result);
            return;
        }

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->setId($sessionOrderId);
            $order->load($sessionOrderId);
            $response = $this->getServiceFromContainer(PaymentService::class)->doCapturePayPalOrder(
                $order,
                $sessionAcdcOrderId
            );
            $result = [
                'location' => [
                    'cl=order&fnc=finalizeacdc'
                    ]
            ];
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage());
            $this->getServiceFromContainer(PaymentService::class)->removeTemporaryOrder();
        }

        $this->outputJson($result);
    }

    public function finalizeuapm(): string
    {
        $uapmRequestId = (string) Registry::getRequest()->getRequestParameter('token');
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionUapmOrderId = PayPalSession::getUapmCheckoutOrderId();

        if (!$sessionOrderId || !$sessionUapmOrderId || ($uapmRequestId !== $sessionUapmOrderId)) {
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

    public function finalizeacdc(): string
    {
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionAcdcOrderId = PayPalSession::getAcdcCheckoutOrderId();

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterExternalPayment($sessionAcdcOrderId);
            $goNext = 'thankyou';
        } catch (\Exception $exception) {
            $this->canceluapm('cannot finalize order');
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
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

    /**
     * Encodes and sends response as json
     *
     * @param $response
     */
    protected function outputJson($response)
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json');

        $utils->showMessageAndExit(json_encode($response));
    }

    protected function _getNextStep($success) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_UAPMINPROGRESS == $success) &&
            ($redirectLink = PayPalSession::getUapmRedirectLink())
        ) {
            PayPalSession::unsetUapmRedirectLink();
            throw new Redirect($redirectLink);
        }

        if (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_ACDCINPROGRESS == $success) {
            return $success;
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
