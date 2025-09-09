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
use OxidEsales\Eshop\Core\ShopVersion;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPal\Module;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthorizationWithAdditionalData;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
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
    private $paymentExecutionError = self::PAYMENT_ERROR_NONE;

    /**
     * @var EshopSession
     */
    private $eshopSession;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /** @var ServiceFactory */
    private $serviceFactory;

    /** @var PatchRequestFactory */
    private $patchRequestFactory;

    /** @var OrderRequestFactory */
    private $orderRequestFactory;

    /** @var SCAValidatorInterface */
    private $scaValidator;

    /** @var ModuleSettings */
    private $moduleSettingsService;

    private $logger;

    private OrderProcessTrackingService $orderProcessTrackingService;

    public function __construct(
        EshopSession $eshopSession,
        OrderRepository $orderRepository,
        SCAValidatorInterface $scaValidator,
        ModuleSettings $moduleSettingsService,
        LoggerInterface $logger,
        OrderProcessTrackingService $orderProcessTrackingService,
        ?ServiceFactory $serviceFactory = null,
        PatchRequestFactory $patchRequestFactory = null,
        OrderRequestFactory $orderRequestFactory = null
    ) {
        $this->eshopSession = $eshopSession;
        $this->orderRepository = $orderRepository;
        $this->scaValidator = $scaValidator;
        $this->moduleSettingsService = $moduleSettingsService;
        $this->logger = $logger;
        $this->orderProcessTrackingService = $orderProcessTrackingService;
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
        string $payPalPartnerAttributionId = '',
        string $returnUrl = null,
        string $cancelUrl = null,
        bool $setProvidedAddress = true
    ): ?Order {
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());
        $customId = $this->getCurrentOrderNumber($basket);

        $request = $this->orderRequestFactory->getRequest(
            $basket,
            $intent,
            $userAction,
            $customId,
            $processingInstruction,
            $paymentSource,
            null,
            $returnUrl,
            $cancelUrl,
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
        } catch (Exception $exception) {
            if (
                $this->moduleSettingsService->getPayPalDebugLevel() === 'debug'
                || $this->moduleSettingsService->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', 'Error on order create call.', [$exception->getMessage()]);
            }
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
        }

        return $response;
    }

    public function doCreatePatchedOrder(
        EshopModelBasket $basket
    ): array {
        $config = Registry::getConfig();
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $captureStrategy = $moduleSettings->getPayPalStandardCaptureStrategy();
        $intent = $captureStrategy === 'directly' ? OrderRequest::INTENT_CAPTURE : OrderRequest::INTENT_AUTHORIZE;
        $debug = $moduleSettings->isSandbox() ? '&XDEBUG_SESSION_START=1' : '';
        $paymentId = Registry::getSession()->getVariable('paymentid');
        $userAction = $paymentId === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID ?
            OrderRequestFactory::USER_ACTION_CONTINUE : OrderRequestFactory::USER_ACTION_PAY_NOW;
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizeacdc' . $debug;
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=ajaxpay&fnc=cancelShopOrder' . $debug;

        // PatchOrders access an OrderCall that has taken place before.
        // For this reason, the payPalPartnerAttributionId does not have
        // to be transmitted again in the case of a PatchCall
        $payPalOrder = $this->doCreatePayPalOrder(
            $basket,
            $intent,
            $userAction,
            null,
            null,
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $returnUrl,
            $cancelUrl,
            false
        );

        $paypalOrderId = '';
        $status = '';

        if ($payPalOrder) {
            $paypalOrderId = $payPalOrder->id ?: '';
            $status = $payPalOrder->status ?: '';
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($basket->getOrderId());

        // patch the order only if paypalOrderId exists
        if ($paypalOrderId && $payPalOrder->status !== 'COMPLETED') {
            $this->doPatchPayPalOrder(
                $basket,
                $paypalOrderId,
                $this->getCustomIdParameter($order)
            );
        }

        $return = [
            'id' => $paypalOrderId,
            'status' => $status
        ];

        if ($status === 'PAYER_ACTION_REQUIRED' || $status === 'CREATED') {
            $return['links'] = $payPalOrder->links;
        }

        return $return;
    }

    /**
     * @throws \OxidSolutionCatalysts\PayPalApi\Exception\ApiException
     */
    public function doPatchPayPalOrder(
        EshopModelBasket $basket,
        string $payPalOrderId,
        string $shopOrderId = ''
    ): void {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());

        // Update Order
        try {
            $orderService->updateOrder(
                $payPalOrderId,
                $this->patchRequestFactory->getOrderPatches($basket, $shopOrderId),
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (Exception $exception) {
            if (
                $this->moduleSettingsService->getPayPalDebugLevel() === 'debug'
                || $this->moduleSettingsService->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', 'Error on order patch call.', [$exception]);
            }
            throw $exception;
        }
    }

    public function doCapturePayPalOrder(
        EshopModelOrder $order,
        string $checkoutOrderId,
        string $paymentId,
        Order $payPalOrder = null
    ): Order {

        /** @var Order $payPalOrder */
        if (is_null($payPalOrder) || !isset($payPalOrder->payment_source)) {
            $payPalOrder = $this->fetchOrderFields($checkoutOrderId);
        }

        //Verify 3D result if acdc payment
        if (!$this->verify3D($paymentId, $payPalOrder)) {
            throw oxNew(StandardException::class, 'OSC_PAYPAL_3DSECURITY_ERROR');
        }

        /** @var ApiPaymentService $paymentService */
        $paymentService = Registry::get(ServiceFactory::class)->getPaymentService();
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());

        // Capture Order
        try {
            //TODO: split into multiple methods
            if ($payPalOrder->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE) {
                // if order approved then authorize
                if ($payPalOrder->status === Order::STATUS_APPROVED) {
                    $request = new OrderAuthorizeRequest();
                    $payPalOrder = $orderService->authorizePaymentForOrder(
                        '',
                        $checkoutOrderId,
                        $request,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                }

                /** @var AuthorizationWithAdditionalData $authorization */
                $authorization = $payPalOrder->purchase_units[0]->payments->authorizations[0];
                $authorizationId = $authorization->id;

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
            } elseif ($payPalOrder->status !== Constants::PAYPAL_STATUS_COMPLETED) {
                $request = new OrderCaptureRequest();
                //order number must be resolved before order patching
                $order->load((string)Registry::getSession()->getVariable('sess_challenge'));
                if (!$order->hasOrderNumber()) {
                    $order->setOrderNumber();
                }

                try {
                    /** @var $result Order */
                    $result = $orderService->capturePaymentForOrder(
                        '',
                        $checkoutOrderId,
                        $request,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                } catch (ApiException $exception) {
                    $this->handlePayPalApiError($exception);

                    $issue = $exception->getErrorIssue();
                    $this->displayErrorIfInstrumentDeclined($issue);
                    throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
                }
            } else {
                // Order is captured, so we set the provided payPalOrder as result
                $result = $payPalOrder;
            }

            $payPalTransactionId = $result && isset($result->purchase_units[0]->payments->captures[0]->id) ?
                $result->purchase_units[0]->payments->captures[0]->id : '';

            $status = $result && $result->purchase_units[0]->payments->captures[0]->status ?
                $result->purchase_units[0]->payments->captures[0]->status : Order::STATUS_SAVED;

            /** @var PayPalOrderModel $paypalOrder */
            $this->trackPayPalOrder(
                $order->getId(),
                $checkoutOrderId,
                $paymentId,
                $status,
                (string)$payPalTransactionId
            );

            if ($result instanceof Order && $order->isPayPalOrderCompleted($result)) {
                //save vault to user and set success message
                $session = Registry::getSession();
                $vault = null;

                if ($paypal = $result->payment_source->paypal) {
                    $vault = $paypal->attributes->vault;
                } elseif ($card = $result->payment_source->card) {
                    $vault = $card->attributes->vault;
                }

                if ($vault->status === "VAULTED") {
                    $vaultSuccess = false;

                    if ($id = $vault->customer["id"]) {
                        $user = Registry::getConfig()->getUser();

                        $user->oxuser__oscpaypalcustomerid = new Field($id);

                        if ($user->save()) {
                            $vaultSuccess = true;
                        }
                    }

                    if (!$vaultSuccess) {
                        if ($this->moduleSettingsService->getPayPalDebugLevel() === 'debug') {
                            $this->logger->log('debug', "Vaulting was attempted but didn't succeed.");
                        }
                    }

                    $session->setVariable("vaultSuccess", $vaultSuccess);
                } else {
                    $session->deleteVariable("vaultSuccess");
                }

                $order->markOrderPaid();
                $order->setTransId((string)$payPalTransactionId);
            }
        } catch (Exception $exception) {
            if ($this->moduleSettingsService->getPayPalDebugLevel() === 'debug') {
                $this->logger->log('debug', 'Warning on order capture call.', [$exception->getMessage()]);
            }
            throw oxNew(StandardException::class, 'OSC_PAYPAL_ORDEREXECUTION_ERROR');
        }

        return $result;
    }

    /**
     * @throws \OxidSolutionCatalysts\PayPalApi\Exception\ApiException
     * @throws \OxidSolutionCatalysts\PayPal\Exception\PayPalException
     */
    public function doConfirmUAPM(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $checkoutOrderId,
        string $paymentSourceId
    ): string {
        $redirectLink = '';

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = Registry::get(ConfirmOrderRequestFactory::class);
        /** @var ConfirmOrderRequest $request */
        $request = $requestFactory->getRequest(
            $basket,
            $paymentSourceId
        );

        // toDo: Clearing with Marcus. Optional. Verifies that the payment originates from a valid,
        // user-consented device and application. Reduces fraud and decreases declines.
        // Transactions that do not include a client metadata ID are not eligible for PayPal Seller Protection.
        $payPalClientMetadataId = '';

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        /** @var Order $response */
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
            $response->status
        );

        return $redirectLink;
    }

    /**
     * Return the PaymentId from session basket
     */
    public function getSessionPaymentId(): ?string
    {
        return $this->eshopSession->getBasket() ? $this->eshopSession->getBasket()->getPaymentId() : null;
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
        if (!$sessionOrderId) {
            return;
        }

        $orderModel = oxNew(EshopModelOrder::class);
        $orderModel->load($sessionOrderId);

        if (
            $orderModel->isLoaded()
        ) {
            $orderModel->cancelOrder();
            if ($this->moduleSettingsService->getPayPalDebugLevel() === 'debug') {
                $this->logger->log('debug', sprintf(
                    'Temporary order with id %s was canceled',
                    $sessionOrderId
                ));
            }
            if (!$orderModel->hasOrderNumber()) {
                $orderModel->delete();
                if ($this->moduleSettingsService->getPayPalDebugLevel() === 'debug') {
                    $this->logger->log('debug', sprintf(
                        'Temporary order without Order number and with id %s was deleted',
                        $sessionOrderId
                    ));
                }
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
            if (
                $this->moduleSettingsService->getPayPalDebugLevel() === 'debug'
                || $this->moduleSettingsService->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', $exception->getMessage(), [$exception]);
            }
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
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            null,
            null,
            false
        );

        $result = '';
        if ($response) {
            $result = $response->id ?: '';
        }
        return $result;
    }

    /**
     * Perform the authorization process for a PayPal payment
     *
     * @param string $checkoutOrderId The PayPal order ID
     * @param string|null $shopOrderId The shop order ID
     * @param string $paymentId The payment ID
     * @return array The result of the authorization process
     * @throws Exception
     */
    public function doAuthorizePayment(string $checkoutOrderId, ?string $shopOrderId, string $paymentId): array
    {
        // Load order if shopOrderId is provided
        $order = null;
        if ($shopOrderId) {
            $order = oxNew(EshopModelOrder::class);
            $order->load($shopOrderId);
        }

        /** @var ApiPaymentService $apiPaymentService */
        $apiPaymentService = Registry::get(ServiceFactory::class)->getPaymentService();
        /** @var ApiOrderService $orderService */
        $orderService = Registry::get(ServiceFactory::class)->getOrderService();

        // Get PayPal order details
        $payPalOrder = $this->fetchOrderFields($checkoutOrderId);
        $verify3DResult = $this->verify3D($paymentId, $payPalOrder);
        $language = Registry::getLang();

        if (!$verify3DResult) {
            return [
                'status' => 'error',
                'message' => $language->translateString('OSC_PAYPAL_3DSECURITY_ERROR')
            ];
        }

        if ($payPalOrder->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE) {
            // if order approved then authorize
            if (
                $payPalOrder->status === PayPalApiOrder::STATUS_APPROVED
                || $payPalOrder->status === PayPalApiOrder::STATUS_CREATED
            ) {
                $request = new OrderAuthorizeRequest();
                $payPalOrder = $orderService->authorizePaymentForOrder(
                    '',
                    $checkoutOrderId,
                    $request,
                    '',
                    Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                );
                $payPalOrder->intent = Constants::PAYPAL_ORDER_INTENT_AUTHORIZE;

                $authorization = $payPalOrder->purchase_units[0]->payments->authorizations[0];

                if ($authorization->status === 'DENIED') {
                    return [
                        'status' => 'error',
                        'message' => $language->translateString(
                            'OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR'
                        )
                    ];
                }

                //here the attributes object of payment source is available, so we can check vaulting status
                $session = Registry::getSession();
                $vault = null;

                if ($paypal = $payPalOrder->payment_source->paypal) {
                    $vault = $paypal->attributes->vault;
                } elseif ($card = $payPalOrder->payment_source->card) {
                    $vault = $card->attributes->vault;
                }

                if ($vault->status === "VAULTED") {
                    $vaultSuccess = false;

                    if ($id = $vault->customer["id"]) {
                        $user = Registry::getConfig()->getUser();

                        $user->oxuser__oscpaypalcustomerid = new Field($id);

                        if ($user->save()) {
                            $vaultSuccess = true;
                        }
                    }

                    if (!$vaultSuccess && $this->moduleSettingsService->getPayPalDebugLevel() === 'debug') {
                        $this->logger->log('debug', "Vaulting was attempted but didn't succeed.");
                    }

                    $session->setVariable("vaultSuccess", $vaultSuccess);
                }
            }

            $authorizationId = $authorization->id;

            // check if we need a reauthorization
            $timeAuthorizationValidity = time()
                - strtotime($payPalOrder->update_time ?? '')
                + Constants::PAYPAL_AUTHORIZATION_VALIDITY;
            if ($timeAuthorizationValidity <= 0) {
                $reAuthorizeRequest = new ReauthorizeRequest();
                $apiPaymentService->reauthorizeAuthorizedPayment(
                    $authorizationId,
                    $reAuthorizeRequest,
                    Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                );
            }

            // track authorization if order is available
            if ($order) {
                $this->trackPayPalOrder(
                    $shopOrderId,
                    $checkoutOrderId,
                    (string)$order->getFieldData('oxpaymenttype'),
                    $authorization->status,
                    $authorizationId,
                    Constants::PAYPAL_TRANSACTION_TYPE_AUTH
                );
            }

            $result = $this->fetchOrderFields($checkoutOrderId);

            return [
                'paymentStatus' => $payPalOrder->getCapturePaymentStatus() ? 'success' : 'error',
                'status' => 'success',
                'payPalOrder' => $result
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Order intent is not AUTHORIZE'
            ];
        }
    }

    public function doExecutePuiPayment(
        EshopModelOrder $order,
        EshopModelBasket $basket,
        string $payPalClientMetadataId = ''
    ): bool {
        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        $payPalOrderId = '';
        try {
            $result = $this->doCreatePayPalOrder(
                $basket,
                Constants::PAYPAL_ORDER_INTENT_CAPTURE,
                null,
                Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
                PayPalDefinitions::PAYMENT_SOURCE_PUI,
                $payPalClientMetadataId,
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
            if ($result) {
                $payPalOrderId = $result->id;
            }
        } catch (Exception $exception) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
            if (
                $this->moduleSettingsService->getPayPalDebugLevel() === 'debug'
                || $this->moduleSettingsService->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', 'Error on pui order creation call.', [$exception]);
            }
        }

        # TODO: check what we created, ensure it is a pui order
        # $paymentSource = $this->fetchOrderFields((string) $payPalOrderId, 'payment_source');
        # $this->logger->log('error', serialize($paymentSource));

        if (!$payPalOrderId) {
            return false;
        }

        $this->trackPayPalOrder(
            (string)$order->getId(),
            $payPalOrderId,
            $basket->getPaymentId(),
            $result->status
        );

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
    ) {
        /** @var PayPalOrderModel $payPalOrder */
        return $this->orderRepository->paypalOrderByOrderIdAndPayPalId(
            $shopOrderId,
            $payPalOrderId,
            $payPalTransactionId
        );
    }

    /**
     * @throws \OxidSolutionCatalysts\PayPalApi\Exception\ApiException
     */
    public function fetchOrderFields(string $paypalOrderId, string $fields = ''): Order
    {
        $orderService = $this->serviceFactory->getOrderService();
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());

        return $orderService
            ->showOrderDetails(
                $paypalOrderId,
                $fields,
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
    }

    public function verify3D(string $paymentId, Order $payPalOrder): bool
    {
        //no ACDC OR Gpay payment
        if (!in_array($paymentId, [PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID, PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID])) {
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

    private function handlePayPalApiError(ApiException $exception): void
    {
        $issue = $exception->getErrorIssue();
        if (self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED === 'PUI_' . $issue) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED);
        } elseif (self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR === 'PUI_' . $issue) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR);
        } elseif (PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID === $this->getSessionPaymentId()) {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
        } elseif (self::PAYMENT_ERROR_INSTRUMENT_DECLINED === 'PAYPAL_ERROR_' . $issue) {
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
                $translatedErrorMessage,
                false,
                true,
                'paypal_error'
            );
        }
    }

    /**
     * @param EshopModelOrder|null $order
     * @return mixed|null
     */
    public function getCustomIdParameter(?EshopModelOrder $order): string
    {
        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $module = oxNew(\OxidEsales\Eshop\Core\Module\Module::class);
        $module->load(Module::MODULE_ID);
        $orderNumber = '';
        /** @var Order $orderNumber */
        if ($order instanceof EshopModelOrder) {
            $orderNumber = (int) $order->getFieldData('oxordernr');
            if ($orderNumber === 0) {
                $order->setOrderNumber();
                $orderNumber = $order->getFieldData('oxordernr');
            }
        }
        if ($moduleSettings->isCustomIdSchemaStructural()) {
            $customID = [
                'oxordernr' => $orderNumber,
                'moduleVersion' => $module->getInfo('version'),
                'oxidVersion' => ShopVersion::getVersion()
            ];

            return json_encode($customID);
        }

        return $orderNumber;
    }

    /**
     * @param \OxidEsales\Eshop\Application\Model\Basket $basket
     * @return string
     */
    public function getCurrentOrderNumber(EshopModelBasket $basket): string
    {
        $customId = '';
        /** @var \OxidSolutionCatalysts\PayPal\Service\Payment $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $basket = Registry::getSession()->getBasket();
        /** @var EshopModelOrder $order */
        $order = oxNew(EshopModelOrder::class);
        $shopOrderOxid = $basket->getOrderId();
        if (!empty($shopOrderOxid)) {
            $order->load($shopOrderOxid);
            $customId = $paymentService->getCustomIdParameter($order);
        }

        return $customId;
    }
}
