<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;

/**
 * Class OrderController
 * @package OxidSolutionCatalysts\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;
    use JsonTrait;

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
            throw new RedirectWithMessage(
                Registry::getConfig()->getShopSecureHomeURL() . 'cl=user',
                'OSC_PAYPAL_LOG_IN_TO_CONTINUE'
            );
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

        if (
            $paymentService->getSessionPaymentId() === PayPalDefinitions::SEPA_PAYPAL_PAYMENT_ID ||
            $paymentService->getSessionPaymentId() === PayPalDefinitions::CCALTERNATIVE_PAYPAL_PAYMENT_ID ||
            $paymentService->getSessionPaymentId() === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID ||
            $paymentService->getSessionPaymentId() === PayPalDefinitions::PAYLATER_PAYPAL_PAYMENT_ID
        ) {
            $paymentService->removeTemporaryOrder();
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if ($moduleSettings->getIsVaultingActive() && $this->getUser()->oxuser__oxpassword->value) {
            $this->addTplParam('oscpaypal_payment_saveable', true);
        }

        $selectedVaultPaymentSourceIndex = Registry::getSession()->getVariable("selectedVaultPaymentSourceIndex");
        $config = Registry::getConfig();
        if (
            !is_null($selectedVaultPaymentSourceIndex) &&
            $payPalCustomerId = $config->getUser()->getFieldData("oscpaypalcustomerid")
        ) {
            $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();

            $selectedPaymentToken = $vaultingService->getVaultPaymentTokenByIndex(
                $payPalCustomerId,
                $selectedVaultPaymentSourceIndex
            );
            //find out which payment token was selected by getting the index via request param
            $paymentType = key($selectedPaymentToken["payment_source"]);
            $paymentSource = $selectedPaymentToken["payment_source"][$paymentType];

            $paymentDescription = "";
            if ($paymentType === "card") {
                $string = Registry::getLang()->translateString("OSC_PAYPAL_CARD_ENDING_IN");
                $paymentDescription = $paymentSource["brand"] . " " . $string . $paymentSource["last_digits"];
            } elseif ($paymentType === "paypal") {
                $string = Registry::getLang()->translateString("OSC_PAYPAL_CARD_PAYPAL_PAYMENT");
                $paymentDescription = $string . " " . $paymentSource["email_address"];
            }

            $this->addTplParam("vaultedPaymentDescription", $paymentDescription);
        }

        return parent::render();
    }

    protected function renderRetryOrderExecution(): bool
    {
        $retryRequest = Registry::getRequest()->getRequestParameter(self::RETRY_OSC_PAYMENT_REQUEST_PARAM);

        $order = oxNew(EshopModelOrder::class);
        $order->load(Registry::getSession()->getVariable('sess_challenge'));

        if (
            !$order->getFieldData('oxtransid') &&
            $retryRequest &&
             isset($this->retryPaymentMessages[$retryRequest])
        ) {
            $displayError = oxNew(DisplayError::class);
            $displayError->setMessage($this->retryPaymentMessages[$retryRequest]);
            Registry::getUtilsView()->addErrorToDisplay($displayError);

            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            if (in_array((string)$paymentService->getSessionPaymentId(), $this->removeTemporaryOrderOnRetry, true)) {
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
        $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');
        $sessionAcdcOrderId = (string) PayPalSession::getCheckoutOrderId();
        $acdcStatus = Registry::getSession()->getVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS);

        if (
            $sessionOrderId &&
            $sessionAcdcOrderId &&
            $acdcStatus === Constants::PAYPAL_STATUS_COMPLETED
        ) {
            //we already have a completed acdc order
            $this->outputJson(['acdcerror' => 'shop order already completed']);
            return;
        }

        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            $paymentService->removeTemporaryOrder();
            Registry::getSession()->setVariable('sess_challenge', $this->getUtilsObjectInstance()->generateUID());

            $status = $this->execute();
        } catch (Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log('error', $exception->getMessage(), [$exception]);
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

        if (!$status || (PayPalOrderModel::ORDER_STATE_ACDCINPROGRESS !== (int)$status)) {
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
        $acdcStatus = Registry::getSession()->getVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS);

        /** @var Logger $logger */
        $logger = $this->getServiceFromContainer(Logger::class);

        if (
            'COMPLETED' === $acdcStatus &&
            $sessionOrderId &&
            $sessionAcdcOrderId
        ) {
            $logger->log(
                'debug',
                'captureAcdcOrder already COMPLETED for PayPal Order id ' . $sessionAcdcOrderId
            );

            $result = [
                'location' => [
                    'cl=order&fnc=finalizeacdc'
                ]
            ];
            $this->outputJson($result);
            return;
        }

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

            // At this point we only trigger the capture. We find out that order was really captured via the
            // CHECKOUT.ORDER.COMPLETED webhook, where we mark the order as paid
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
            //track status in session
            Registry::getSession()->setVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS, $response->status);
        } catch (Exception $exception) {
            $logger->log(
                'debug',
                $exception->getMessage(),
                [$exception]
            );
            $this->getServiceFromContainer(PaymentService::class)->removeTemporaryOrder();
        }

        $this->outputJson($result);
    }

    public function finalizepaypalsession(): string
    {
        $standardRequestId = (string) Registry::getRequest()->getRequestParameter('token');
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $sessionCheckoutOrderId = PayPalSession::getCheckoutOrderId();
        $vaulting = Registry::getRequest()->getRequestParameter("vaulting");

        $cancelSession = !$sessionOrderId ||
            !$sessionCheckoutOrderId ||
            ($standardRequestId !== $sessionCheckoutOrderId);
        if (!$vaulting && $cancelSession) {
            $this->cancelpaypalsession('request to session mismatch');
        }

        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);

            /** @var PayPalApiModelOrder $payPalOrder */
            $payPalOrder = $paymentService->fetchOrderFields((string) $sessionCheckoutOrderId, '');
            $vaultingPaymentCompleted = $vaulting && $payPalOrder->status == "COMPLETED";
            if (!$vaultingPaymentCompleted && 'APPROVED' !== $payPalOrder->status) {
                throw PayPalException::sessionPaymentFail(
                    'Unexpected status ' . $payPalOrder->status . ' for PayPal order ' . $sessionCheckoutOrderId
                );
            }

            $deliveryAddress = PayPalAddressResponseToOxidAddress::mapOrderDeliveryAddress($payPalOrder);
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $paymentsId = $order->getFieldData('oxpaymenttype');
            $isButtonPayment = PayPalDefinitions::isButtonPayment($paymentsId);
            if ($isButtonPayment) {
                $order->assign($deliveryAddress);
            }
            $order->finalizeOrderAfterExternalPayment($sessionCheckoutOrderId);
            $order->save();
        } catch (PayPalException $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log(
                'debug',
                'PayPal Checkout error during order finalization ' . $exception->getMessage(),
                [$exception]
            );
            $this->cancelpaypalsession('cannot finalize order');
            return 'payment?payerror=2';
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
        } catch (Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log(
                'error',
                'failure during finalizeOrderAfterExternalPayment',
                [$exception]
            );
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

    protected function _getNextStep($success) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            (PayPalOrderModel::ORDER_STATE_SESSIONPAYMENT_INPROGRESS === $success) &&
            ($redirectLink = PayPalSession::getSessionRedirectLink())
        ) {
            PayPalSession::unsetSessionRedirectLink();
            throw new Redirect($redirectLink);
        }

        if (PayPalOrderModel::ORDER_STATE_ACDCINPROGRESS === $success) {
            return (string) $success;
        }

        if (PaymentService::PAYMENT_ERROR_PUI_PHONE === $success) {
            //user needs to retry, entered pui phone number was not accepted by PayPal
            return 'order?retryoscpp=puiretry';
        }

        if (PayPalOrderModel::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS === $success) {
            return 'order';
        }

        if (PayPalOrderModel::ORDER_STATE_NEED_CALL_ACDC_FINALIZE === $success) {
            return 'order?fnc=finalizeacdc';
        }

        if (PayPalOrderModel::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS === $success) {
            return 'order?fnc=finalizeacdc&fallbackfinalize=1';
        }

        if (PayPalOrderModel::ORDER_STATE_ACDCCOMPLETED === $success) {
            return 'order?fnc=finalizeacdc&fallbackfinalize=1';
        }

        if (
            EshopModelOrder::ORDER_STATE_ORDEREXISTS === $success &&
            Registry::getSession()->getVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS) ===
            Constants::PAYPAL_STATUS_COMPLETED
        ) {
            Registry::getSession()->deleteVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS);
            PayPalSession::unsetPayPalSession();
        }

        return parent::_getNextStep($success);
    }
}
