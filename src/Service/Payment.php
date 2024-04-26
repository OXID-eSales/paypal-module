<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session as EshopSession;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings as ModuleSettingsService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthorizationWithAdditionalData;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\CaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\ReauthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as ApiOrderService;
use OxidSolutionCatalysts\PayPalApi\Service\Payments as ApiPaymentService;
use Psr\Log\LoggerInterface;

class Payment
{
    use ServiceContainer;

    public const PAYMENT_ERROR_NONE = 'PAYPAL_PAYMENT_ERROR_NONE';
    public const PAYMENT_ERROR_GENERIC = 'PAYPAL_PAYMENT_ERROR_GENERIC';
    public const PAYMENT_ERROR_PUI_PHONE = 'PAYPAL_PAYMENT_ERROR_PUI_PHONE';
    public const PAYMENT_ERROR_PUI_GENERIC = 'PAYPAL_PAYMENT_ERROR_PUI_GENRIC';
    public const PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED = 'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED';
    public const PAYMENT_SOURCE_DECLINED_BY_PROCESSOR = 'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR';
    public const PAYMENT_ERROR_INSTRUMENT_DECLINED = 'PAYPAL_ERROR_INSTRUMENT_DECLINED';

    /**
     * @var string
     */
    private string $paymentExecutionError = self::PAYMENT_ERROR_NONE;

    /**
     * @var EshopSession
     */
    private EshopSession $eshopSession;

    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;

    /** @var ServiceFactory */
    private ServiceFactory $serviceFactory;

    /** @var PatchRequestFactory */
    private PatchRequestFactory $patchRequestFactory;

    /** @var OrderRequestFactory */
    private OrderRequestFactory $orderRequestFactory;

    /** @var SCAValidatorInterface */
    private SCAValidatorInterface $scaValidator;

    /** @var ModuleSettingsService */
    private ModuleSettings $moduleSettingsService;

    private LoggerInterface $logger;

    public function __construct(
        EshopSession $eshopSession,
        OrderRepository $orderRepository,
        SCAValidatorInterface $scaValidator,
        ModuleSettingsService $moduleSettingsService,
        LoggerInterface $logger,
        ServiceFactory $serviceFactory = null,
        PatchRequestFactory $patchRequestFactory = null,
        OrderRequestFactory $orderRequestFactory = null
    ) {
        $this->eshopSession = $eshopSession;
        $this->orderRepository = $orderRepository;
        $this->scaValidator = $scaValidator;
        $this->moduleSettingsService = $moduleSettingsService;
        $this->logger = $logger;
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
        bool $setProvidedAddress = true,
        ?EshopModelOrder $order = null
    ): ?ApiModelOrder {
        //TODO return value
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $transactionId = method_exists($order, 'getPaypalIntData')
                        ? $order->getPaypalIntData('oxordernr') : null;
        $request = $this->orderRequestFactory->getRequest(
            $basket,
            $intent,
            $userAction,
            (string)$transactionId,
            $processingInstruction,
            $paymentSource,
            null,
            $returnUrl,
            $cancelUrl,
            $withArticles,
            $setProvidedAddress
        );

        $response = null;

        try {
            $response = $orderService->createOrder(
                $request,
                $payPalPartnerAttributionId,
                $payPalClientMetadataId,
                'return=minimal'
            );
        } catch (ApiException $exception) {
            $this->handlePayPalApiError($exception);
            $this->logger->log('error', 'Error on order create call.', [$exception]);
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
        }

        return $response;
    }

    /**
     * @throws Exception
     */
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
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            null,
            null,
            false
        );

        if (null == $response) {
            throw new Exception('Creating paypal order error');
        }

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
    ): void {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->patchRequestFactory
            ->getRequest($basket, $shopOrderId);

        // Update Order
        try {
            $orderService->updateOrder(
                $checkoutOrderId,
                $request,
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (Exception $exception) {
            $this->logger->log('error', 'Error on order patch call.', [$exception]);
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

        // Capture Order
        try {
            //TODO: split into multiple methods
            if ($payPalOrder->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE) {
                // if order approved then authorize
                if ($payPalOrder->status === ApiOrderModel::STATUS_APPROVED) {
                    $request = new OrderAuthorizeRequest();
                    $payPalOrder = $orderService->authorizePaymentForOrder(
                        '',
                        $checkoutOrderId,
                        $request,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                }
                $authorization = null;
                $authorizationId = null;
                    /** @var AuthorizationWithAdditionalData $authorization */
                $paymentCollection = $payPalOrder->purchase_units[0]->payments;
                if ($paymentCollection) {
                    $authorization = $paymentCollection->authorizations ? $paymentCollection->authorizations[0] : null;
                    $authorizationId = $authorization ? $authorization->id : null;
                }

                // check if we need a reauthorization
                $timeAuthorizationValidity = time()
                    - strtotime($payPalOrder->update_time ?? '')
                    + Constants::PAYPAL_AUTHORIZATION_VALIDITY;
                if ($timeAuthorizationValidity <= 0) {
                    $reAuthorizeRequest = new ReauthorizeRequest();
                    $paymentService->reauthorizeAuthorizedPayment(
                        $authorizationId,
                        $reAuthorizeRequest,
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                }
                /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
                $oxpaymenttype = $order->getPaypalStringData('oxpaymenttype');
                //track authorization
                /** @var \OxidEsales\Eshop\Application\Model\Order $order */
                $this->trackPayPalOrder(
                    $order->getId(),
                    $checkoutOrderId,
                    $oxpaymenttype,
                    $authorization ? (string)$authorization->status : '',
                    (string)$authorizationId,
                    Constants::PAYPAL_TRANSACTION_TYPE_AUTH
                );

                // capture
                $request = new CaptureRequest();
                try {
                    $paymentService->captureAuthorizedPayment(
                        $authorizationId,
                        $request,
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                } catch (ApiException $exception) {
                    $this->handlePayPalApiError($exception);

                    $issue = $exception->getErrorIssue();
                    $this->displayErrorIfInstrumentDeclined($issue);

                    throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
                }

                $result = $this->fetchOrderFields($checkoutOrderId);
            } elseif (Registry::getRequest()->getRequestParameter("vaulting")) {
                //when a vaulted payment is used, the order is already finished.
                $result = $this->fetchOrderFields($checkoutOrderId);
            } else {
                $request = new OrderCaptureRequest();
                //order number must be resolved before order patching
                /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
                $order->setOrderNumber();

                try {
                    //Patching the order with OXID order number as custom value
                    $this->doPatchPayPalOrder(
                        Registry::getSession()->getBasket(),
                        $checkoutOrderId,
                        $order->getPaypalStringData('oxordernr')
                    );
                    /** @var ApiOrderModel $result */
                    $result = $orderService->capturePaymentForOrder(
                        '',
                        $checkoutOrderId,
                        $request,
                        ''
                    );
                } catch (ApiException $exception) {
                    $this->handlePayPalApiError($exception);

                    $issue = $exception->getErrorIssue();
                    $this->displayErrorIfInstrumentDeclined($issue);
                    throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
                }
            }

            $payPalTransactionId = '';
            $status = ApiOrderModel::STATUS_SAVED;
            $paymentCollection = $result->purchase_units[0]->payments;
            if ($paymentCollection) {
                $captures = $paymentCollection->captures;
                if ($captures) {
                    $payPalTransactionId = $captures[0]->id;
                    $status = $captures[0]->status;
                }
            }

            $this->trackPayPalOrder(
                $order->getId(),
                $checkoutOrderId,
                $paymentId,
                (string)$status,
                (string)$payPalTransactionId
            );

            /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
            if ($order->isPayPalOrderCompleted($result)) {
                //save vault to user and set success message
                $session = Registry::getSession();
                $vault = null;

                $paymentSourceResponse = $result->payment_source;
                if (null != $paymentSourceResponse) {
                    $paypal = $paymentSourceResponse->paypal;
                    if ($paypal) {
                        $paypalWalletAttributesResponse = $paypal->attributes;
                        if ($paypalWalletAttributesResponse) {
                            $vault = $paypalWalletAttributesResponse->vault;
                        }
                    } elseif ($card = $paymentSourceResponse->card) {
                        $cardAttributesResponse = $card->attributes;
                        if ($cardAttributesResponse) {
                            $vault = $cardAttributesResponse->vault;
                        }
                    }
                }

                if ($vault && $session->getVariable("vaultSuccess") && $vault->status == "VAULTED") {
                    $vaultSuccess = false;

                    if ($id = $vault->customer["id"]) {
                        $user = Registry::getConfig()->getUser();

                        $user->oxuser__oscpaypalcustomerid = new Field($id);

                        if ($user->save()) {
                            $vaultSuccess = true;
                        }
                    }

                    if (!$vaultSuccess) {
                        $this->logger->log('debug', "Vaulting was attempted but didn't succeed.");
                    }

                    $session->setVariable("vaultSuccess", $vaultSuccess);
                } else {
                    $session->deleteVariable("vaultSuccess");
                }

                $order->markOrderPaid();
                $order->setTransId((string)$payPalTransactionId);
            }
        } catch (Exception $exception) {
            //Webhook might try to capture already captured order
            $this->logger->log('debug', 'Warning on order capture call.', [$exception]);
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
            $request,
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
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
            (string)$order->getId(),
            $checkoutOrderId,
            $basket->getPaymentId(),
            null != $response->status ? $response->status : '400' //@TODO check if 400 is best status code here
        );

        return $redirectLink;
    }

    /**
     * Return the PaymentId from session basket
     */
    public function getSessionPaymentId(): ?string
    {
        $basket = $this->eshopSession->getBasket();
        return method_exists($basket, "getPaymentId") ? $basket->getPaymentId() : null;
    }

    /**
     * Does the given payment id belong to PayPal
     */
    public function isPayPalPayment(string $paymentId = ''): bool
    {
        $sessionPaymentId = $paymentId ?: (string)$this->getSessionPaymentId();
        return PayPalDefinitions::isPayPalPayment($sessionPaymentId);
    }

    public function removeTemporaryOrder(): void
    {
        $sessionOrderId = $this->eshopSession->getVariable('sess_challenge');
        if (!is_string($sessionOrderId)) {
            return;
        }

        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $orderModel */
        $orderModel = oxNew(EshopModelOrder::class);
        $orderModel->load($sessionOrderId);

        if ($orderModel->isLoaded()) {
            if ($orderModel->hasOrderNumber()) {
                $this->logger->log('debug', 'Cannot delete valid order with id ' . $sessionOrderId);
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
            $this->logger->log('error', $exception->getMessage(), [$exception]);
        }

        //NOTE: payment not fully executed, we need customer interaction first
        return $redirectLink;
    }

    /**
     * @throws PayPalException
     * @throws Exception
     */
    public function doExecuteStandardPayment(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $intent = Constants::PAYPAL_ORDER_INTENT_CAPTURE
    ): string {

        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        //For Standard payment we should not yet have a PayPal order in session.
        //We create a fresh PayPal order at this point
        $config = Registry::getConfig();
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';
        $redirectLink = false;

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

        if (null == $response) {
            throw new Exception('Creating paypal order error');
        }

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
            if ($links['rel'] === 'approve' || $links['rel'] === 'payer-action') {
                $redirectLink = $links['href'];
                break;
            }
        }

        //no customer interaction needed if a vaulted payment is used
        if ($response->status === Constants::PAYPAL_STATUS_COMPLETED) {
            return $returnUrl . "&vaulting=true";
        }

        if (!$redirectLink) {
            PayPalSession::unsetPayPalSession();
            $this->removeTemporaryOrder();
            throw PayPalException::sessionPaymentMissingRedirectLink();
        }

        //NOTE: payment not fully executed, we need customer interaction first
        return $redirectLink;
    }

    /**
     * @throws Exception
     */
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

        if (null == $response) {
            throw new Exception('Creating paypal order error');
        }

        return $response->id ?: '';
    }

    public function doExecutePuiPayment(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $payPalClientMetadataId = ''
    ): bool {
        $payPalOrderId = null;
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);
        $response = null;
        try {
            $response = $this->doCreatePayPalOrder(
                $basket,
                Constants::PAYPAL_ORDER_INTENT_CAPTURE,
                null,
                Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
                PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME,
                $payPalClientMetadataId,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
                null,
                null,
                true,
                true,
                $order
            );

            if (null == $response) {
                throw new Exception('Creating paypal order error');
            }

            $payPalOrderId = $response->id;
        } catch (UserPhoneException $e) {
            //mistyped phone in last order step
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_PHONE);
        } catch (Exception $exception) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
            $this->logger->log('error', 'Error on pui order creation call.', [$exception]);
        }

        # TODO: check what we created, ensure it is a pui order
        # $paymentSource = $this->fetchOrderFields((string) $payPalOrderId, 'payment_source');
        # $this->logger->log('error', serialize($paymentSource));

        if (empty($payPalOrderId)) {
            return false;
        }
        if (isset($response)) {
            $this->trackPayPalOrder(
                (string)$order->getId(),
                $payPalOrderId,
                $basket->getPaymentId(),
                null != $response->status ? $response->status : '400' //@TODO check if 400 is best status code here
            );
        }

        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order->savePuiInvoiceNr($payPalOrderId);

        return (bool)$payPalOrderId;
    }

    public function setPaymentExecutionError(string $text): void
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
    ): PayPalOrderModel {
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
        //case check is to be done automatic, but we have no result to check
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

    private function handlePayPalApiError(ApiException $exception): void
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

    private function displayErrorIfInstrumentDeclined(?string $issue): void
    {
        if ($issue === 'INSTRUMENT_DECLINED') {
            $languageObject = Registry::getLang();
            $translatedErrorMessage = $languageObject->translateString(
                self::PAYMENT_ERROR_INSTRUMENT_DECLINED,
                (int)$languageObject->getBaseLanguage(),
                false
            );
            Registry::getUtilsView()->addErrorToDisplay(
                is_array($translatedErrorMessage)
                    ? implode(" ", $translatedErrorMessage) : $translatedErrorMessage,
                false,
                true,
                'paypal_error'
            );
        }
    }
}
