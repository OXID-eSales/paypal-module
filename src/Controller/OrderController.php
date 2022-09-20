<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrderModel;

/**
 * Class OrderController
 * @package OxidSolutionCatalysts\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;

    public const RETRY_OSC_PAYMENT_REQUEST_PARAM = 'retryoscpp';

    private $removeTemporaryOrderOnRetry = [
        PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
        PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID
    ];

    private $retryPaymentMessages = [
        'acdcretry' => 'OSC_PAYPAL_ACDC_PLEASE_RETRY',
        'puiretry'  => 'OSC_PAYPAL_PUI_PLEASE_RETRY'
    ];

    public function render()
    {
        if (Registry::getSession()->getVariable('oscpaypal_payment_redirect')) {
            Registry::getSession()->deleteVariable('oscpaypal_payment_redirect');
            Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeURL() . 'cl=user', true, 302);
        }

        $this->addTplParam('oscpaypal_executing_order', false);
        $isRetry = $this->renderRetryOrderExecution();

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        if (!$isRetry && $paymentService->isOrderExecutionInProgress()) {
            $displayError = oxNew(DisplayError::class);
            $displayError->setMessage('OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS');
            Registry::getUtilsView()->addErrorToDisplay($displayError);
            $this->addTplParam('oscpaypal_executing_order', true);
        }

        if ($paymentService->getSessionPaymentId() === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID) {
            $paymentService->removeTemporaryOrder();
        }

        return parent::render();
    }

    protected function renderRetryOrderExecution(): bool
    {
        $retryRequest = Registry::getRequest()->getRequestParameter(self::RETRY_OSC_PAYMENT_REQUEST_PARAM);

        if ($retryRequest && isset($this->retryPaymentMessages[$retryRequest])) {
            $displayError = oxNew(DisplayError::class);
            $displayError->setMessage($this->retryPaymentMessages[$retryRequest]);
            Registry::getUtilsView()->addErrorToDisplay($displayError);

            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            if (in_array((string) $paymentService->getSessionPaymentId(), $this->removeTemporaryOrderOnRetry)) {
                $paymentService->removeTemporaryOrder();
            }
            return true;
        }

        return false;
    }

    public function getUserCountryIso(): string
    {
        $userRepository = $this->getServiceFromContainer(UserRepository::class);
        return $userRepository->getUserCountryIso();
    }

    public function getUserStateIso(): string
    {
        $userRepository = $this->getServiceFromContainer(UserRepository::class);
        return $userRepository->getUserStateIso();
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

        if (!$status || (\OxidSolutionCatalysts\PayPal\Model\Order::ORDER_STATE_ACDCINPROGRESS != $status)) {
            $response = ['acdcerror' => 'unexpected order status ' . $status];
            $paymentService->removeTemporaryOrder();
        } else {
            PayPalSession::storePayPalOrderId($paypalOrderId);
            $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');
            $payPalOrder = $paymentService->getPayPalCheckoutOrder($sessionOrderId, $paypalOrderId);
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

            //TODO: if capture fails, do we still end up with an order?
            $response = $this->getServiceFromContainer(PaymentService::class)->doCapturePayPalOrder(
                $order,
                $sessionAcdcOrderId,
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
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
            $payPalOrder = $paymentService->fetchOrderFields((string) $sessionCheckoutOrderId, '');
            if ('APPROVED' !== $payPalOrder->status) {
                throw PayPalException::sessionPaymentFail();
            }

            $deliveryAddress = PayPalAddressResponseToOxidAddress::mapOrderDeliveryAddress($payPalOrder);
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $paymentsId = $order->getFieldData('oxpaymenttype');
            $isPayPalExpress = $paymentsId === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID;
            if ($isPayPalExpress) {
                $order->assign($deliveryAddress);
            }
            $order->finalizeOrderAfterExternalPayment($sessionCheckoutOrderId);
            $order->save();
        } catch (\Exception $exception) {
            Registry::getLogger()->debug(
                'PayPal Checkout error during order finalization ' . $exception->getMessage(),
                [$exception]
            );
            $this->cancelpaypalsession('cannot finalize order');
        }

        return 'thankyou';
    }

    public function finalizeacdc(): string
    {
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionAcdcOrderId = PayPalSession::getCheckoutOrderId();

        $forceFetchDetails = (bool) Registry::getRequest()->getRequestParameter('fallbackfinalize');

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterExternalPayment($sessionAcdcOrderId, $forceFetchDetails);
            $goNext = 'thankyou';
        } catch (\Exception $exception) {
            Registry::getLogger()->error('failure during finalizeOrderAfterExternalPayment', [$exception]);
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
            (PayPalOrderModel::ORDER_STATE_SESSIONPAYMENT_INPROGRESS == $success) &&
            ($redirectLink = PayPalSession::getSessionRedirectLink())
        ) {
            PayPalSession::unsetSessionRedirectLink();
            throw new Redirect($redirectLink);
        }

        if (PayPalOrderModel::ORDER_STATE_ACDCINPROGRESS == $success) {
            return (string) $success;
        }

        if (PaymentService::PAYMENT_ERROR_PUI_PHONE == $success) {
            //user needs to retry, entered pui phone number was not accepted by PayPal
            return 'order?retryoscpp=puiretry';
        }

        if (PayPalOrderModel::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS == $success) {
            return 'order';
        }

        if (PayPalOrderModel::ORDER_STATE_NEED_CALL_ACDC_FINALIZE == $success) {
            return 'order?fnc=finalizeacdc';
        }

        if (PayPalOrderModel::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS == $success) {
            return 'order?fnc=finalizeacdc&fallbackfinalize=1';
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
