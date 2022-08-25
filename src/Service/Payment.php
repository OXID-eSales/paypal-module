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
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
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
    public const PAYMENT_ERROR_NONE = 'PAYPAL_PAYMENT_ERROR_NONE';
    public const PAYMENT_ERROR_GENERIC = 'PAYPAL_PAYMENT_ERROR_GENERIC';
    public const PAYMENT_ERROR_PUI_PHONE = 'PAYPAL_PAYMENT_ERROR_PUI_PHONE';
    public const PAYMENT_ERROR_PUI_GENERIC = 'PAYPAL_PAYMENT_ERROR_PUI_GENRIC';
    public const PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED = 'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED';
    public const PAYMENT_SOURCE_DECLINED_BY_PROCESSOR = 'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR';

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

    /** @var ConfirmOrderRequestFactory */
    private $confirmOrderRequestFactory;

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
        OrderRequestFactory $orderRequestFactory = null,
        ConfirmOrderRequestFactory $confirmOrderRequestFactory = null
    ) {
        $this->eshopSession = $eshopSession;
        $this->orderRepository = $orderRepository;
        $this->scaValidator = $scaValidator;
        $this->moduleSettingsService = $moduleSettingsService;
        $this->serviceFactory = $serviceFactory ?: Registry::get(ServiceFactory::class);
        $this->patchRequestFactory = $patchRequestFactory ?: Registry::get(PatchRequestFactory::class);
        $this->orderRequestFactory = $orderRequestFactory ?: Registry::get(OrderRequestFactory::class);
        $this->confirmOrderRequestFactory = $confirmOrderRequestFactory ?:
            Registry::get(ConfirmOrderRequestFactory::class);
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
        bool $withArticles = true
        #): ?ApiModelOrder
    ) { //TODO return value

        $this->setPaymentExecutionError(self::PAYMENT_ERROR_NONE);

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

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
            $withArticles
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
            Registry::getLogger()->error("Api error on order create call. " . $exception->getErrorIssue(), [$exception]);
            $this->handlePayPalApiError($exception);
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order create call.", [$exception]);
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

        $this->doPatchPayPalOrder(
            $basket,
            $paypalOrderId
        );

        $return = [
            'id' => $paypalOrderId,
            'status' => $status
        ];

        return $return;
    }

    public function doPatchPayPalOrder(
        EshopModelBasket $basket,
        string $checkoutOrderId,
        string $paymentSource = '',
        string $shopOrderId = ''
    ): void {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->patchRequestFactory
            ->getRequest($basket, $shopOrderId, $paymentSource);

        // Update Order
        try {
            $orderService->updateOrder($checkoutOrderId, $request);
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order patch call.", [$exception]);
            throw $exception;
        }
    }

    public function doCapturePayPalOrder(
        EshopModelOrder $order,
        string $checkoutOrderId,
        string $paymentId
    ): ApiOrderModel {

        $payPalOrder = $this->fetchOrderFields($checkoutOrderId, 'payment_source');

        //Verify 3D result if acdc payment
        if (!$this->verify3D($paymentId, $payPalOrder)) {
            throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
        }

        /** @var ApiPaymentService $paymentService */
        $paymentService = Registry::get(ServiceFactory::class)->getPaymentService();
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        // Capture Order
        try {
            if ($payPalOrder->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE) {
                // if order approved then authorize
                if ($payPalOrder->status === ApiOrderModel::STATUS_APPROVED) {
                    $request = new OrderAuthorizeRequest();
                    $payPalOrder = $orderService->authorizePaymentForOrder('', $checkoutOrderId, $request, '');
                }

                $authorizationId = $payPalOrder->purchase_units[0]->payments->authorizations[0]->id;

                // check if we need a reauthorization
                $timeAuthorizationValidity = time()
                    - strtotime($payPalOrder->update_time)
                    + Constants::PAYPAL_AUTHORIZATION_VALIDITY;
                if ($timeAuthorizationValidity <= 0) {
                    $reAuthorizeRequest = new ReauthorizeRequest();
                    $paymentService->reauthorizeAuthorizedPayment($authorizationId, $reAuthorizeRequest);
                }

                // capture
                $request = new CaptureRequest();
                $paymentService->captureAuthorizedPayment($authorizationId, $request, '');
                $result = $this->fetchOrderFields($checkoutOrderId);
                $payPalTransactionId = $result->purchase_units[0]->payments->captures[0]->id;
            } else {
                $request = new OrderCaptureRequest();
                /** @var ApiOrderModel */
                $result = $orderService->capturePaymentForOrder('', $checkoutOrderId, $request, '');
                $payPalTransactionId = $result->purchase_units[0]->payments->captures[0]->id;
            }

            /** @var PayPalOrderModel $paypalOrder */
            $this->trackPayPalOrder(
                $order->getId(),
                $checkoutOrderId,
                $paymentId,
                ApiOrderModel::STATUS_SAVED,
                (string) $payPalTransactionId
            );
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
            throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
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
     */
    public function getSessionPaymentId(): ?string
    {
        return $this->eshopSession->getBasket() ? $this->eshopSession->getBasket()->getPaymentId() : null;
    }

    /**
     * Return the PaymentId from session basket
     */
    public function isPayPalPayment(): bool
    {
        $sessionPaymentId = $this->getSessionPaymentId();
        return in_array($sessionPaymentId, [
            PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID,
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
            PayPalDefinitions::PAYLATER_PAYPAL_PAYMENT_ID,
            PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID,
            PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID
        ], true) || PayPalDefinitions::isUAPMPayment($sessionPaymentId);
    }

    public function removeTemporaryOrder(): void
    {
        $sessionOrderId = $this->eshopSession->getVariable('sess_challenge');
        if (!$sessionOrderId) {
            return;
        }

        $orderModel = oxNew(EshopModelOrder::class);
        if ($orderModel->load($sessionOrderId)) {
            $orderModel->delete();
        }

        if ($payPalOrderId = PayPalSession::getCheckoutOrderId()) {
            $payPalOrder = $this->orderRepository->paypalOrderByOrderIdAndPayPalId($sessionOrderId, $payPalOrderId);
            $payPalOrder->delete();
        }

        PayPalSession::unsetPayPalOrderId();
        $this->eshopSession->deleteVariable('sess_challenge');
    }

    /**
     * @throws PayPalException
     */
    public function doExecuteUAPMPayment(EshopModelOrder $order, EshopModelBasket $basket): string
    {
        //For UAPM payment we should not yet have a paypal order in session.
        //We create a fresh paypal order at this point

        $uapmOrderId = $this->doCreateUAPMOrder($basket);
        if (!$uapmOrderId) {
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
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
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
        //For Standard payment we should not yet have a paypal order in session.
        //We create a fresh paypal order at this point
        $config = Registry::getConfig();
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';

        $response = $this->doCreatePayPalOrder(
            $basket,
            $intent,
            null,
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
            PayPalSession::unsetPayPalOrderId();
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
            Registry::getLogger()->error("Error on pui order creation call.", [$exception]);
        }

       # TODO: check what we created, ensure it is a pui order
       # $paymentSource = $this->fetchOrderFields((string) $payPalOrderId, 'payment_source');
       # Registry::getLogger()->error(serialize($paymentSource));

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
        string $payPalTransactionId = ''
    ): PayPalOrderModel {
        /** @var PayPalOrderModel $payPalOrder */
        $payPalOrder = $this->getPayPalCheckoutOrder($shopOrderId, $payPalOrderId, $payPalTransactionId);

        $payPalOrder->setPaymentMethodId($paymentMethodId);
        $payPalOrder->setStatus($status);
        $payPalOrder->setTransactionId($payPalTransactionId);
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
    public function verify3D(string $paymentId, ApiOrderModel $payPalOrder ): bool
    {
        //no ACDC payment
        if ($paymentId != PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID) {
            return true;
        }
        //case no check is needed
        if ( $this->moduleSettingsService->alwaysIgnoreSCAResult()) {
            return true;
        }
        //case check is to be done automatic but we have no result to check
        if ((Constants::PAYPAL_SCA_WHEN_REQUIRED === $this->moduleSettingsService->getPayPalSCAContingency()) &&
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
        if (self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED == 'PUI_' . $exception->getErrorIssue()) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED);
        } elseif (self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR == 'PUI_' . $exception->getErrorIssue()) {
            $this->setPaymentExecutionError(self::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR);
        } elseif (PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID == $this->getSessionPaymentId()){
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_PUI_GENERIC);
        } else {
            $this->setPaymentExecutionError(self::PAYMENT_ERROR_GENERIC);
        }
    }
}
