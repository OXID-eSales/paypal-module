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
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;

/**
 * Class OrderController
 * @package OxidSolutionCatalysts\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;

    public function render()
    {
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

    /**
     * @psalm-suppress InternalMethod
     */
    public function createAcdcOrder(): void
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $paymentService->removeTemporaryOrder();
        Registry::getSession()->setVariable('sess_challenge', $this->getUtilsObjectInstance()->generateUID());

        try {
            $status = $this->execute();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            $this->outputJson(['acdcerror' => 'failed to execute shop order']);
            return;
        }

        $response = $paymentService->doCreatePatchedOrder(
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
            PayPalSession::storePayPalOrderId($paypalOrderId);
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
        $sessionAcdcOrderId = (string) PayPalSession::getCheckoutOrderId();

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

    public function finalizepaypalsession(): string
    {
        $standardRequestId = (string) Registry::getRequest()->getRequestParameter('token');
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionCheckoutOrderId = PayPalSession::getCheckoutOrderId();

        if (!$sessionOrderId || !$sessionCheckoutOrderId || ($standardRequestId !== $sessionCheckoutOrderId)) {
            $this->cancelpaypalsession('request to session mismatch');
        }

        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            $order = $paymentService->fetchOrderFields((string) $sessionCheckoutOrderId, '');
            if ('APPROVED' !== $order->status) {
                throw PayPalException::sessionPaymentFail();
            }

            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterExternalPayment($sessionCheckoutOrderId);
        } catch (\Exception $exception) {
            $this->cancelpaypalsession('cannot finalize order');
        }

        return 'thankyou';
    }

    public function finalizeacdc(): string
    {
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionAcdcOrderId = PayPalSession::getCheckoutOrderId();

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterExternalPayment($sessionAcdcOrderId);
            $goNext = 'thankyou';
        } catch (\Exception $exception) {
            $this->cancelpaypalsession('cannot finalize order');
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
    }

    public function cancelpaypalsession(string $errorcode = null): string
    {
        //TODO: we get the PayPal order id retuned in token parameter, can be used for paranoia checks
        //(string) Registry::getRequest()->getRequestParameter('token')
        $requestErrorcode = (string) Registry::getRequest()->getRequestParameter('errorcode');

        $this->getServiceFromContainer(PaymentService::class)
            ->removeTemporaryOrder();

        $goNext = 'payment';
        if ($errorcode || $requestErrorcode) {
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
    }

    /**
     * Template-Getter get a Fraudnet CmId
     *
     * @param $response
     * @psalm-suppress InternalMethod
     */
    public function getPayPalPuiFraudnetCmId(): string
    {

        if (!($cmId = PayPalSession::getPayPalPuiCmId())) {
            $cmId = Registry::getUtilsObject()->generateUId();
            PayPalSession::storePayPalPuiCmId($cmId);
        }
        return $cmId;
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
            (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_SESSIONPAYMENT_INPROGRESS == $success) &&
            ($redirectLink = PayPalSession::getSessionRedirectLink())
        ) {
            PayPalSession::unsetSessionRedirectLink();
            throw new Redirect($redirectLink);
        }

        if (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_ACDCINPROGRESS == $success) {
            return (string)$success;
        }

        return parent::_getNextStep($success);
    }

    private function setPayPalAsPaymentMethod()
    {
        $payment = $this->getBasket()->getPaymentId();
        if (($payment !== PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID)) {
            $this->getBasket()->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        }
    }
}
