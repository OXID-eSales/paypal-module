<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use Exception;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session as EshopSession;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalLogger;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthorizationWithAdditionalData;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\CaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\ReauthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidSolutionCatalysts\PayPalApi\Service\Payments as ApiPaymentService;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as ApiOrderService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings as ModuleSettingsService;

class Payment
{
    const PAYMENT_ERROR_NONE = 'PAYPAL_PAYMENT_ERROR_NONE';
    const PAYMENT_ERROR_GENERIC = 'PAYPAL_PAYMENT_ERROR_GENERIC';
    const PAYMENT_ERROR_PUI_PHONE = 'PAYPAL_PAYMENT_ERROR_PUI_PHONE';
    const PAYMENT_ERROR_PUI_GENERIC = 'PAYPAL_PAYMENT_ERROR_PUI_GENRIC';
    const PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED = 'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED';
    const PAYMENT_SOURCE_DECLINED_BY_PROCESSOR = 'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR';

    const PAYMENT_ERROR_INSTRUMENT_DECLINED = 'PAYPAL_ERROR_INSTRUMENT_DECLINED';

    /**
     * @var string
     */
    private $paymentExecutionError = self::PAYMENT_ERROR_NONE;

    /**
     * @var EshopSession
     */
    private $eshopSession;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /** ServiceFactory */
    private $serviceFactory;

    /** PatchRequestFactory */
    private $patchRequestFactory;

    /** OrderRequestFactory */
    private $orderRequestFactory;

    /** @var SCAValidatorInterface */
    private $scaValidator;

    /** @var ModuleSettingsService */
    private $moduleSettingsService;

    public function __construct(
        EshopSession $eshopSession,
        OrderRepository $orderRepository,
        SCAValidatorInterface $scaValidator,
        ModuleSettingsService $moduleSettingsService,
        ServiceFactory $serviceFactory = null,
        PatchRequestFactory $patchRequestFactory = null,
        OrderRequestFactory $orderRequestFactory = null
    ) {
        $this->eshopSession = $eshopSession;
        $this->orderRepository = $orderRepository;
        $this->scaValidator = $scaValidator;
        $this->moduleSettingsService = $moduleSettingsService;
        $this->serviceFactory = $serviceFactory ?: Registry::get(ServiceFactory::class);
        $this->patchRequestFactory = $patchRequestFactory ?: Registry::get(PatchRequestFactory::class);
        $this->orderRequestFactory = $orderRequestFactory ?: Registry::get(OrderRequestFactory::class);
    }

    public function doCreatePayPalOrder(
        EshopModelBasket $basket,
        string $intent,
        string $userAction = null,
        string $processingInstruction = null,
        string $paymentSource = null,
        string $payPalClientMetadataId = '',
        string $payPalRequestId = '',
        string $payPalPartnerAttributionId = '',
        string $returnUrl = null,
        string $cancelUrl = null,
        bool $withArticles = true,
        bool $setProvidedAddress = true
        #): ?ApiModelOrder
    ) { //TODO return value

        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();
        $logger = new PayPalLogger();

        $request = $this->orderRequestFactory->getRequest(
            $basket,
            $intent,
            $userAction,
            null,
            $processingInstruction,
            $paymentSource,
            null,
            $returnUrl,
            $cancelUrl,
            $withArticles,
            $setProvidedAddress
        );

        $response = [];

        try {
            $response = $orderService->createOrder(
                $request,
                $payPalPartnerAttributionId,
                $payPalClientMetadataId,
                'return=minimal',
                $payPalRequestId
            );
        } catch (ApiException $exception) {
            $logger->error("Api error on order create call. " .
                                         $exception->getErrorIssue(), [$exception]);
            $this->handlePayPalApiError($exception);
        } catch (Exception $exception) {
            $logger->error("Error on order create call.", [$exception]);
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
        }

        return $response;
    }

    public function doCreatePatchedOrder(
        EshopModelBasket $basket
    ): array {
        // PatchOrders access an OrderCall that has taken place before.
        // For this reason, the payPalPartnerAttributionId does not have
        // to be transmitted again in the case of a PatchCall
        $response = $this->doCreatePayPalOrder(
            $basket,
            Constants::PAYPAL_ORDER_INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            null,
            '',
            '',
            '',
            null,
            null,
            false
        );

        $paypalOrderId = $response->id ?: '';
        $status = $response->status ?: '';

        // patch the order only if paypalOrderId exists
        if ($paypalOrderId) {
            $this->doPatchPayPalOrder(
                $basket,
                $paypalOrderId
            );
        }

        $return = [
            'id' => $paypalOrderId,
            'status' => $status
        ];

        return $return;
    }

    public function doPatchPayPalOrder(
        EshopModelBasket $basket,
        string $checkoutOrderId,
        string $shopOrderId = ''
    ) {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->patchRequestFactory
            ->getRequest($basket, $shopOrderId);

        // Update Order
        try {
            $orderService->updateOrder($checkoutOrderId, $request);
        } catch (Exception $exception) {
            $logger = new PayPalLogger();
            $logger->error("Error on order patch call.", [$exception]);
            throw $exception;
        }
    }

    public function doCapturePayPalOrder(
        EshopModelOrder $order,
        string $checkoutOrderId,
        string $paymentId,
        ApiOrderModel $payPalOrder = null
    ): ApiOrderModel {

        /** @var ApiOrderModel $payPalOrder */
        $payPalOrder = is_null($payPalOrder) || !isset($payPalOrder->payment_source) ?
            $this->fetchOrderFields($checkoutOrderId, 'payment_source') :
            $payPalOrder;

        //Verify 3D result if acdc payment
        if (!$this->verify3D($paymentId, $payPalOrder)) {
            throw oxNew(StandardException::class, 'OSC_PAYPAL_3DSECURITY_ERROR');
        }

        /** @var ApiPaymentService $paymentService */
        $paymentService = Registry::get(ServiceFactory::class)->getPaymentService();
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();
        $logger = new PayPalLogger();

        // Capture Order
        try {
            //TODO: split into multiple methods
            if ($payPalOrder->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE) {
                // if order approved then authorize
                if ($payPalOrder->status === ApiOrderModel::STATUS_APPROVED) {
                    $request = new OrderAuthorizeRequest();
                    $payPalOrder = $orderService->authorizePaymentForOrder('', $checkoutOrderId, $request, '');
                }

                /** @var AuthorizationWithAdditionalData $authorization */
                $authorization = $payPalOrder->purchase_units[0]->payments->authorizations[0];
                $authorizationId = $authorization->id;

                // check if we need a reauthorization
                $timeAuthorizationValidity = time()
                    - strtotime($payPalOrder->update_time)
                    + Constants::PAYPAL_AUTHORIZATION_VALIDITY;
                if ($timeAuthorizationValidity <= 0) {
                    $reAuthorizeRequest = new ReauthorizeRequest();
                    $paymentService->reauthorizeAuthorizedPayment($authorizationId, $reAuthorizeRequest);
                }

                //track authorization
                $this->trackPayPalOrder(
                    $order->getId(),
                    $checkoutOrderId,
                    (string) $order->getFieldData('oxpaymenttype'),
                    $authorization->status,
                    $authorizationId,
                    Constants::PAYPAL_TRANSACTION_TYPE_AUTH
                );

                // capture
                $request = new CaptureRequest();
                try {
                    /** @var Capture $capture */
                    $capture = $paymentService->captureAuthorizedPayment($authorizationId, $request, '');
                } catch (ApiException $exception) {
                    $this->handlePayPalApiError($exception);

                    $issue = $exception->getErrorIssue();
                    $this->displayErrorIfInstrumentDeclined($issue);

                    $logger->error($exception->getMessage(), [$exception]);

                    throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
                }

                $result = $this->fetchOrderFields($checkoutOrderId);
            } else {
                $request = new OrderCaptureRequest();
                try {
                    /** @var ApiOrderModel */
                    $result = $orderService->capturePaymentForOrder('', $checkoutOrderId, $request, '');
                } catch (ApiException $exception) {
                    $this->handlePayPalApiError($exception);

                    $issue = $exception->getErrorIssue();
                    $this->displayErrorIfInstrumentDeclined($issue);

                    $logger->error($exception->getMessage(), [$exception]);
                    throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
                }
            }

            $payPalTransactionId = $result && isset($result->purchase_units[0]->payments->captures[0]->id) ?
                        $result->purchase_units[0]->payments->captures[0]->id : '';

            $status = $result && $result->purchase_units[0]->payments->captures[0]->status ?
                $result->purchase_units[0]->payments->captures[0]->status : ApiOrderModel::STATUS_SAVED;

            /** @var PayPalOrderModel $paypalOrder */
            $this->trackPayPalOrder(
                $order->getId(),
                $checkoutOrderId,
                $paymentId,
                $status,
                (string) $payPalTransactionId
            );

            if ($result instanceof ApiOrderModel && $order->isPayPalOrderCompleted($result)) {
                $order->setOrderNumber();
                $order->markOrderPaid();
                $order->setTransId((string) $payPalTransactionId);
            }
        } catch (Exception $exception) {
            //Webhook might try to capture already captured order
            $logger->debug("Error on order capture call.", [$exception]);
            throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
        }

        return $result;
    }

    public function doConfirmUAPM(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $checkoutOrderId,
        string $uapmName
    ): string {
        $redirectLink = '';

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = Registry::get(ConfirmOrderRequestFactory::class);
        /** @var ConfirmOrderRequest $request */
        $request = $requestFactory->getRequest(
            $basket,
            $uapmName
        );

        // toDo: Clearing with Marcus. Optional. Verifies that the payment originates from a valid,
        // user-consented device and application. Reduces fraud and decreases declines.
        // Transactions that do not include a client metadata ID are not eligible for PayPal Seller Protection.
        $payPalClientMetadataId = '';

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        /** @var ApiModelOrder $response */
        $response = $orderService->confirmTheOrder(
            $payPalClientMetadataId,
            $checkoutOrderId,
            $request
        );

        if (!isset($response->links)) {
            throw PayPalException::sessionPaymentMalformedResponse();
        }
        foreach ($response->links as $links) {
            if ($links['rel'] === 'payer-action') {
                $redirectLink = $links['href'];
                break;
            }
        }
        if (!$redirectLink) {
            throw PayPalException::sessionPaymentMissingRedirectLink();
        }

        $this->trackPayPalOrder(
            (string) $order->getId(),
            $checkoutOrderId,
            $basket->getPaymentId(),
            $response->status
        );

        return $redirectLink;
    }

    /**
     * Return the PaymentId from session basket
     * @return null|string
     */
    public function getSessionPaymentId()
    {
        return $this->eshopSession->getBasket() ? $this->eshopSession->getBasket()->getPaymentId() : null;
    }

    /**
     * Does the given payment id belong to PayPal
     */
    public function isPayPalPayment(string $paymentId = ''): bool
    {
        $sessionPaymentId = $paymentId ?: (string) $this->getSessionPaymentId();
        return PayPalDefinitions::isPayPalPayment($sessionPaymentId);
    }

    public function removeTemporaryOrder()
    {
        $sessionOrderId = $this->eshopSession->getVariable('sess_challenge');
        if (!$sessionOrderId) {
            return;
        }

        $orderModel = oxNew(EshopModelOrder::class);
        $orderModel->load($sessionOrderId);

        if ($orderModel->isLoaded()) {
            if ($orderModel->hasOrderNumber()) {
                $logger = new PayPalLogger();
                $logger->info('Cannot delete valid order with id ' . $sessionOrderId);
            } else {
                $orderModel->delete();
            }
        }

        PayPalSession::unsetPayPalOrderId();
        $this->eshopSession->deleteVariable('sess_challenge');
    }

    //TODO: payment service is intended to trigger payments with API
    //      all methods for order handling need to go to separate service
    public function isOrderExecutionInProgress(): bool
    {
        $sessionOrderId = $this->eshopSession->getVariable('sess_challenge');
        $payPalOrderId = PayPalSession::getCheckoutOrderId();
        $paymentId = $this->getSessionPaymentId();

        return $sessionOrderId &&
            $payPalOrderId &&
            $paymentId &&
            ((PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID === $paymentId) ||
                PayPalDefinitions::isUAPMPayment($paymentId)
            );
    }

    /**
     * @throws PayPalException
     */
    public function doExecuteUAPMPayment(EshopModelOrder $order, EshopModelBasket $basket): string
    {
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        //For UAPM payment we should not yet have a paypal order in session.
        //We create a fresh paypal order at this point

        $uapmOrderId = $this->doCreateUAPMOrder($basket);
        if (!$uapmOrderId) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
            throw PayPalException::createPayPalOrderFail();
        }

        PayPalSession::storePayPalOrderId($uapmOrderId);
        $redirectLink = '';

        try {
            $redirectLink = $this->doConfirmUAPM(
                $order,
                $basket,
                $uapmOrderId,
                PayPalDefinitions::getPaymentSourceRequestName($basket->getPaymentId())
            );
        } catch (Exception $exception) {
            PayPalSession::unsetPayPalOrderId();
            $this->removeTemporaryOrder();
            //TODO: do we need to log this?
            $logger = new PayPalLogger();
            $logger->error($exception->getMessage(), [$exception]);
        }

        //NOTE: payment not fully executed, we need customer interaction first
        return $redirectLink;
    }

    /**
     * @throws PayPalException
     */
    public function doExecuteStandardPayment(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        $intent = Constants::PAYPAL_ORDER_INTENT_CAPTURE
    ): string {

        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        //For Standard payment we should not yet have a paypal order in session.
        //We create a fresh paypal order at this point
        $config = Registry::getConfig();
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';

        $response = $this->doCreatePayPalOrder(
            $basket,
            $intent,
            OrderRequestFactory::USER_ACTION_PAY_NOW,
            null,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $returnUrl,
            $cancelUrl,
            false
        );

        $orderId = $response->id ?: '';

        if (!$orderId) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
            throw PayPalException::createPayPalOrderFail();
        }

        PayPalSession::storePayPalOrderId($orderId);

        if (!isset($response->links)) {
            throw PayPalException::sessionPaymentMalformedResponse();
        }
        foreach ($response->links as $links) {
            if ($links['rel'] === 'approve') {
                $redirectLink = $links['href'];
                break;
            }
        }
        if (!$redirectLink) {
            PayPalSession::unsetPayPalSession();
            $this->removeTemporaryOrder();
            throw PayPalException::sessionPaymentMissingRedirectLink();
        }

        //NOTE: payment not fully executed, we need customer interaction first
        return $redirectLink;
    }

    public function doCreateUAPMOrder(EshopModelBasket $basket): string
    {
        $response = $this->doCreatePayPalOrder(
            $basket,
            Constants::PAYPAL_ORDER_INTENT_CAPTURE,
            null,
            null,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            null,
            null,
            false
        );

        return $response->id ?: '';
    }

    public function doExecutePuiPayment(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $payPalClientMetadataId = ''
    ): bool {
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);
        try {
            $result = $this->doCreatePayPalOrder(
                $basket,
                Constants::PAYPAL_ORDER_INTENT_CAPTURE,
                null,
                Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
                PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME,
                $payPalClientMetadataId,
                $order->getId(),
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
            $payPalOrderId = $result->id;
        } catch (UserPhoneException $e) {
            //mistyped phone in last order step
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_PHONE);
        } catch (Exception $exception) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
            $logger = new PayPalLogger();
            $logger->error("Error on pui order creation call.", [$exception]);
        }

       # TODO: check what we created, ensure it is a pui order
       # $paymentSource = $this->fetchOrderFields((string) $payPalOrderId, 'payment_source');
       # $logger = new PayPalLogger();
       # $logger->error(serialize($paymentSource));

        if (!$payPalOrderId) {
            return false;
        }

        $this->trackPayPalOrder(
            (string) $order->getId(),
            $payPalOrderId,
            $basket->getPaymentId(),
            $result->status
        );

        $order->savePuiInvoiceNr($payPalOrderId);

        return (bool) $payPalOrderId;
    }

    public function setPaymentExecutionError(string $text)
    {
        $this->paymentExecutionError = $text;
    }

    public function getPaymentExecutionError(): string
    {
        return $this->paymentExecutionError;
    }

    public function trackPayPalOrder(
        string $shopOrderId,
        string $payPalOrderId,
        string $paymentMethodId,
        string $status,
        string $payPalTransactionId = '',
        string $transactionType = Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
    ): PayPalOrderModel {
        /** @var PayPalOrderModel $payPalOrder */
        $payPalOrder = $this->getPayPalCheckoutOrder($shopOrderId, $payPalOrderId, $payPalTransactionId);

        $payPalOrder->setPaymentMethodId($paymentMethodId);
        $payPalOrder->setStatus($status);
        $payPalOrder->setTransactionId($payPalTransactionId);
        $payPalOrder->setTransactionType($transactionType);
        $payPalOrder->save();

        return $payPalOrder;
    }

    public function getPayPalCheckoutOrder(
        string $shopOrderId,
        string $payPalOrderId,
        string $payPalTransactionId = ''
    ) {
        /** @var PayPalOrderModel $payPalOrder */
        return $this->orderRepository->paypalOrderByOrderIdAndPayPalId(
            $shopOrderId,
            $payPalOrderId,
            $payPalTransactionId
        );
    }

    public function fetchOrderFields(string $paypalOrderId, string $fields = ''): ApiOrderModel
    {
        return $this->serviceFactory
            ->getOrderService()
            ->showOrderDetails($paypalOrderId, $fields);
    }

    /**
     * @throws StandardException
     */
    public function verify3D(string $paymentId, ApiOrderModel $payPalOrder): bool
    {
        //no ACDC payment
        if ($paymentId != PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID) {
            return true;
        }
        //case no check is needed
        if ($this->moduleSettingsService->alwaysIgnoreSCAResult()) {
            return true;
        }
        //case check is to be done automatic but we have no result to check
        if (
            (Constants::PAYPAL_SCA_WHEN_REQUIRED === $this->moduleSettingsService->getPayPalSCAContingency()) &&
            is_null($this->scaValidator->getCardAuthenticationResult($payPalOrder))
        ) {
            return true;
        }
        //Verify 3D result if acdc payment
        if ($this->scaValidator->isCardUsableForPayment($payPalOrder)) {
            return true;
        }

        return false;
    }

    private function handlePayPalApiError(ApiException $exception)
    {
        $issue = $exception->getErrorIssue();
        if (self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED == 'PUI_' . $issue) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED);
        } elseif (self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR == 'PUI_' . $issue) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR);
        } elseif (PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID == $this->getSessionPaymentId()) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
        } elseif (self::PAYMENT_ERROR_INSTRUMENT_DECLINED == 'PAYPAL_ERROR_' . $issue) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_INSTRUMENT_DECLINED);
        } else {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
        }
    }

    private function displayErrorIfInstrumentDeclined(string $issue)
    {
        if ($issue === 'INSTRUMENT_DECLINED') {
            $languageObject = Registry::getLang();
            $translatedErrorMessage = $languageObject->translateString(
                self::PAYMENT_ERROR_INSTRUMENT_DECLINED,
                (int)$languageObject->getBaseLanguage(),
                false
            );
            Registry::getUtilsView()->addErrorToDisplay(
                $translatedErrorMessage,
                false,
                true,
                'paypal_error'
            );
        }
    }
}
