<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Service\GooglePay\GooglePayPayPalService;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderPayPalService;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use Psr\Log\LoggerInterface;

/**
 * Class OrderController
 *
 * @package OxidSolutionCatalysts\PayPal\Controller
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;
    use JsonTrait;

    private $removeTemporaryOrderOnRetry = [
        PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
        PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID
    ];

    public function init()
    {
        $session = Registry::getSession();
        if (
            $session->getVariable(Constants::SESSION_PSEUDODELIVERYCOSTUSED) &&
            $oBasket = $this->getBasket()
        ) {
            // set deliveryprice to null to force recalculation of deliveryprice
            $oBasket->setDeliveryPrice();
            $session->deleteVariable(Constants::SESSION_PSEUDODELIVERYCOSTUSED);
        }

        parent::init();
    }

    public function render()
    {
        $session = Registry::getSession();
        $lang = Registry::getLang();
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        if ($session->getVariable('oscpaypal_payment_redirect')) {
            $session->deleteVariable('oscpaypal_payment_redirect');
            throw new RedirectWithMessage(
                Registry::getConfig()->getShopSecureHomeURL() . 'cl=user',
                'OSC_PAYPAL_LOG_IN_TO_CONTINUE'
            );
        }

        if ($paymentService->isOrderExecutionInProgress()) {
            $this->cancelpaypalsession();
        }

        $user = $this->getUser();

        if ($user) {
            $paymentId = (string) $paymentService->getSessionPaymentId();

            $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
            $isVaultingPossible = $moduleSettings->isVaultingAllowedForPayment($paymentId)
                && $user->getFieldData('oxpassword');

            //Disable save payments if a payment of the same type is already vaulted
            $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
            if (
                (PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID === $paymentId &&
                 $vaultingService->isVaultedPaymentUsed(
                     PayPalDefinitions::PAYMENT_SOURCE_PAYPAL,
                     $this->getUser()
                 )) ||
                (PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID === $paymentId &&
                 $vaultingService->isVaultedPaymentUsed(
                     PayPalDefinitions::PAYMENT_SOURCE_CARD,
                     $this->getUser()
                 ))
            ) {
                $isVaultingPossible = false;
            }

            $this->addTplParam('oscpaypal_isVaultingPossible', $isVaultingPossible);
            $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();

            if (
                $paymentId === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID ||
                $paymentId === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID
            ) {
                $vaultedPaymentTokenSelected = $vaultingService->fetchSelectedVaultedPaymentToken($this->getUser());
                if ($vaultedPaymentTokenSelected) {
                    $paymentDescription = $lang->translateString("OSC_PAYPAL_VAULTING_USE_HINT");
                    $this->addTplParam("vaultedPaymentDescription", $paymentDescription);
                }
            }
        }

        return parent::render();
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
     * @throws Exception
     */
    public function executeGooglePayOrder(): void
    {
        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);

            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

            $_POST['sDeliveryAddressMD5'] = $this->getDeliveryAddressMD5();
            $orderId = Registry::getRequest()->getRequestParameter('orderID');
            $_POST['orderID'] = $orderId;
            $session = Registry::getSession();
            $session->setVariable('isPayPalPaymentCheckout', true);
            $this->execute();
            $session->deleteVariable('isPayPalPaymentCheckout');
        } catch (Exception $exception) {
            Registry::getSession()->deleteVariable('isPayPalPaymentCheckout');
            $logger->log('error', 'GooglePay order execute failed: ' . $exception->getMessage(), [$exception]);
            $this->outputJson([
                'googlepayerror' => 'failed to execute shop order',
                'status' => 'ERROR'
            ]);
            return;
        }

        try {
            $paymentService->doPatchPayPalOrder(
                Registry::getSession()->getBasket(),
                $orderId
            );
        } catch (ApiException $exception) {
            $logger->log('error', 'GooglePay order patch failed: ' . $exception->getMessage(), [$exception]);

            // The shop order was already persisted by execute() -> finalizeOrder();
            // cancel it and clear the session so the customer returns to a clean
            // order overview where the queued displayError is rendered.
            $sessionOrderId = (string)Registry::getSession()->getVariable('sess_challenge');
            if ($sessionOrderId) {
                $shopOrder = oxNew(EshopModelOrder::class);
                if ($shopOrder->load($sessionOrderId)) {
                    $shopOrder->cancelOrder();
                }
            }
            Registry::getSession()->deleteVariable('sess_challenge');

            $lang = Registry::getLang();
            $message = $lang->translateString(
                'OSC_PAYPAL_ERROR_INVALID_ADDRESS',
                (int)$lang->getBaseLanguage(),
                false
            );
            Registry::getUtilsView()->addErrorToDisplay(
                $message,
                false,
                true,
                'paypal_error'
            );

            $this->outputJson([
                'googlepayerror' => 'failed to patch paypal order',
                'status' => 'ERROR',
            ]);
            return;
        }

        $this->outputJson([
            'status' => 'SUCCESS'
        ]);
    }

    /**
     * @throws \OxidSolutionCatalysts\PayPalApi\Exception\ApiException
     * @throws \OxidSolutionCatalysts\PayPal\Exception\PayPalException
     * @throws \JsonException
     */
    public function captureGooglePayOrder(): void
    {
        $sessionOrderId = Registry::getSession()->getVariable('sess_challenge');
        $order = oxNew(EshopModelOrder::class);
        $order->load($sessionOrderId);
        $orderService = Registry::get(ServiceFactory::class)->getOrderService();
        $orderId = (string) Registry::getRequest()->getRequestParameter('orderID');
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        /** @var SCAValidator $scaValidator */
        $scaValidator = $this->getServiceFromContainer(SCAValidatorInterface::class);
        $payPalApiOrder = $paymentService->fetchOrderFields($orderId);
        $verify3DResult = $scaValidator->verify3D(
            PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID,
            $payPalApiOrder
        );

        if (!$verify3DResult) {
            throw PayPalException::cannotFinalizeOrderAfterExternalPayment(
                $orderId,
                PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID
            );
        }

        $request = new OrderCaptureRequest();
        try {
            $orderService->capturePaymentForOrder(
                '',
                $orderId,
                $request,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (ApiException $exception) {
            $issue = $exception->getErrorIssue();
            $languageObject = Registry::getLang();
            $translatedErrorMessage = $languageObject->translateString(
                'OSC_PAYPAL_' . $issue,
                (int)$languageObject->getBaseLanguage(),
                false
            );
            $displayError = oxNew(DisplayError::class);
            $displayError->setMessage($translatedErrorMessage);
            Registry::getUtilsView()->addErrorToDisplay($displayError);
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('warning', 'GooglePay capture failed or refused: ' . $exception->getMessage(), [$exception]);
        }

        $this->outputJson([
            'token' => $orderId
        ]);
    }

    public function isPayPalCheckoutPayment(): bool
    {
        $payment = $this->getPayment();
        return $payment && PayPalDefinitions::isPayPalPayment($payment->getId());
    }

    public function createApplePayOrder(): void
    {
        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            $paymentService->removeTemporaryOrder();
            $session = Registry::getSession();
            $session->setVariable(
                'sess_challenge',
                $this->getUtilsObjectInstance()->generateUID()
            );
            $_POST['sDeliveryAddressMD5'] = $this->getDeliveryAddressMD5();
            $_POST['ord_agb'] = (int)filter_var($_POST['checkAgbTop'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $_POST['oxdownloadableproductsagreement'] = (int)filter_var($_POST['oxdownloadableproductsagreement'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $_POST['oxserviceproductsagreement'] = (int)filter_var($_POST['oxserviceproductsagreement'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $session->setVariable('isPayPalPaymentCheckout', true);
            $status = $this->execute();
            $session->deleteVariable('isPayPalPaymentCheckout');

        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', 'ApplePay createOrder execute failed: ' . $exception->getMessage(), [$exception]);
            $this->outputJson(['error' => 'failed to execute shop order' . $exception->getMessage()]);
            return;
        }

        $response = PayPalSession::getCheckoutOrder();

        if (!($paypalOrderId = $response['id'])) {
            $this->outputJson(['error' => 'cannot create paypal order']);
            return;
        }

        if (!$status) {
            $response = ['error' => 'unexpected order status ' . $status];
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
    public function captureApplePayOrder()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $orderId = (string) Registry::getRequest()->getRequestEscapedParameter('orderID');
        $orderService = Registry::get(ServiceFactory::class)->getOrderService();
        $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');

        if ($orderId === '' || $sessionOrderId === '') {
            $logger->log('warning', 'captureApplePayOrder missing orderID or sessionOrderId');
            throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
        }

        $request = new OrderCaptureRequest();
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        try {
            /** @var $result ApiOrderModel */
            $result = $orderService->capturePaymentForOrder(
                '',
                $orderId,
                $request,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (ApiException $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('warning', 'ApplePay capture failed or refused: ' . $exception->getMessage(), [$exception]);

            throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR' . $exception->getMessage());
        }

        //track status in session
        PayPalSession::storePayPalOrderId($orderId);

        $result = [
            'location' => [
                'cl=order&fnc=finalizeapplepay'
            ]
        ];

        $this->outputJson($result);
    }

    public function finalizeapplepay(): string
    {
        $sessionOrderId = (string) Registry::getSession()->getVariable('sess_challenge');
        $sessionCheckoutOrderId = PayPalSession::getCheckoutOrderId();
        $forceFetchDetails = (bool) Registry::getRequest()->getRequestParameter('fallbackfinalize');

        try {
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            $order->finalizeOrderAfterExternalPayment($sessionCheckoutOrderId, $forceFetchDetails);
            $goNext = 'thankyou';
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log(
                'error',
                'failure during finalizeOrderAfterExternalPayment',
                [$exception]
            );
            $this->getServiceFromContainer(OrderPayPalService::class)
                ->cancelPayPalSession('cannot finalize order');
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
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
            // Check if order was already successfully captured (e.g. by previous request)
            $order = oxNew(EshopModelOrder::class);
            $order->load($sessionOrderId);
            if ($order->isLoaded() &&
                ($order->isOrderSuccessfullyPaid() || !empty($order->getFieldData('oxtransid')))
            ) {
                return 'thankyou';
            }

            $this->getServiceFromContainer(OrderPayPalService::class)
                ->cancelPayPalSession('request to session mismatch');
            return 'payment?payerror=2';
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($sessionOrderId);

        try {
            $paymentService = $this->getServiceFromContainer(PaymentService::class);

            /** @var PayPalApiModelOrder $payPalOrder */
            $payPalOrder = $paymentService->fetchOrderFields((string) $sessionCheckoutOrderId, '');
            $vaultingPaymentCompleted = $vaulting && $payPalOrder->status === "COMPLETED";
            if (
                !$vaultingPaymentCompleted &&
                'APPROVED' !== $payPalOrder->status &&
                'COMPLETED' !== $payPalOrder->status
                ) {
                throw PayPalException::sessionPaymentFail(
                    'Unexpected status ' . $payPalOrder->status . ' for PayPal order ' . $sessionCheckoutOrderId
                );
            }

            $deliveryAddress = PayPalAddressResponseToOxidAddress::mapOrderDeliveryAddress($payPalOrder);
            $paymentsId = $order->getFieldData('oxpaymenttype') ?? '';
            $isButtonPayment = PayPalDefinitions::isButtonPayment($paymentsId);
            if ($isButtonPayment) {
                $order->assign($deliveryAddress);
            }
            $order->finalizeOrderAfterExternalPayment($sessionCheckoutOrderId);
            $order->save();
        } catch (PayPalException $exception) {
            // paranoia check: The order may have already been completely
            // processed by a webhook and therefore cannot be finalized again.
            if (!$order->isOrderSuccessfullyPaid()) {
                /** @var LoggerInterface $logger */
                $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
                $logger->log(
                    'warning',
                    'PayPal session finalize failed, cancelling session: ' . $exception->getMessage(),
                    [$exception]
                );
                $this->getServiceFromContainer(OrderPayPalService::class)
                    ->cancelPayPalSession('cannot finalize order');
                return 'payment?payerror=2';
            }
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
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log(
                'error',
                'failure during finalizeOrderAfterExternalPayment',
                [$exception]
            );
            $this->getServiceFromContainer(OrderPayPalService::class)
                ->cancelPayPalSession('cannot finalize order');
            $goNext = 'payment?payerror=2';
        }

        return $goNext;
    }

    public function finalizeGooglePay(): string
    {
        $paypalOrderId = (string) Registry::getRequest()->getRequestParameter('token');
        $forceFetchDetails = (bool) Registry::getRequest()->getRequestParameter('fallbackfinalize');

        $oxidOrderId = Registry::getSession()->getVariable('sess_challenge');

        /** @var GooglePayPayPalService $googlePayPayPalService */
        $googlePayPayPalService = $this->getServiceFromContainer(GooglePayPayPalService::class);
        $sucesss = $googlePayPayPalService->finalizeGooglePay($oxidOrderId, $paypalOrderId, $forceFetchDetails);

        return $sucesss ?
            'thankyou' :
            $this->getServiceFromContainer(OrderPayPalService::class)
                ->cancelPayPalSession('cannot finalize order');
    }

    public function cancelpaypalsession(string $errorcode = null): void
    {
        $requestErrorcode = (string) Registry::getRequest()->getRequestParameter('errorcode');

        $this->getServiceFromContainer(PaymentService::class)
            ->removeTemporaryOrder();

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $logger->log('info', sprintf(
            'PayPal session canceled (errorcode: %s, request errorcode: %s, sess_challenge: %s)',
            $errorcode ?? 'none',
            $requestErrorcode ?: 'none',
            (string) Registry::getSession()->getVariable('sess_challenge')
        ));

        $goNext = 'cl=payment';
        if ($errorcode || $requestErrorcode) {
            $goNext .= '&payerror=2';
            throw new Redirect(
                Registry::getConfig()->getShopSecureHomeURL() . $goNext
            );
        }

        throw new RedirectWithMessage(
            Registry::getConfig()->getShopSecureHomeURL() . $goNext,
            'OSC_PAYPAL_ORDEREXECUTION_ERROR'
        );
    }

    /**
     * Template-Getter get a Fraudnet CmId
     *
     * @return string
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
            $shopUrl = Registry::getConfig()->getShopUrl();
            $sslShopUrl = Registry::getConfig()->getSslShopUrl();
            if (
                strpos($redirectLink, $shopUrl) !== 0 &&
                strpos($redirectLink, $sslShopUrl) !== 0 &&
                strpos($redirectLink, 'https://www.paypal.com/') !== 0 &&
                strpos($redirectLink, 'https://www.sandbox.paypal.com/') !== 0 &&
                strpos($redirectLink, 'https://paypal.com/') !== 0 &&
                strpos($redirectLink, 'https://sandbox.paypal.com/') !== 0
            ) {
                throw new Redirect($shopUrl);
            }
            throw new Redirect($redirectLink);
        }

        if (PayPalOrderModel::ORDER_STATE_ACDCINPROGRESS === $success) {
            return (string) $success;
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

    public function getCurrentTrackingId(): string
    {
        /** @var OrderProcessTrackingService $orderProcessTrackingService */
        $orderProcessTrackingService = Registry::get(OrderProcessTrackingService::class);
        return $orderProcessTrackingService->getTrackingId();
    }

    public function getDeladrid(): string
    {
        return (string)Registry::getSession()->getVariable('deladrid');
    }

    public function getPayPalCustomerId(): string
    {
        $result = '';
        $user = $this->getUser();
        if ($user) {
            $result = $user->getFieldData('oscpaypalcustomerid');
            $result = !is_null($result) ? $result : '';
        }
        return $result;
    }

    /**
     * Used in the template: checkout_order_btn_submit_bottom.tpl to get the vaulted payment source
     *
     * @return string
     * @throws \JsonException
     */
    public function getVaultedPaymentSource(): string
    {
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $vaultedPaymentTokenSelected = $vaultingService->fetchSelectedVaultedPaymentToken($this->getUser());

        return !empty($vaultedPaymentTokenSelected) ? json_encode([
            "token" => [
                "id" => $vaultedPaymentTokenSelected['id'],
                "type" => "SETUP_TOKEN",
            ]
        ], JSON_THROW_ON_ERROR) : 'null';
    }

    public function findNonMaterialItemsInBasket(): array
    {
        /** @var Basket $basket */
        $basket = $this->getBasket();

        $nonMaterialItems = [];
        if ($basket) {
            $contents = $basket->getContents();
            foreach ($contents as $basketItem) {
                $article = $basketItem->getArticle();
                if ($article && $article->getFieldData('oxnonmaterial')) {
                    $nonMaterialItems[] = $basketItem;
                }
            }
        }

        return $nonMaterialItems;
    }

    public function isNonMaterialItemInBasket(): bool
    {
        return 0 < count($this->findNonMaterialItemsInBasket());
    }
}
