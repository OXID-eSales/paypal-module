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
use OxidSolutionCatalysts\PayPal\Core\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as ApiOrderService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiModelOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;


class Payment
{
    /**
     * @var EshopSession
     */
    protected $eshopSession;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /** ServiceFactory */
    protected $serviceFactory;

    /** PatchRequestFactory */
    protected $patchRequestFactory;

    /** OrderRequestFactory */
    protected $orderRequestFactory;

    /** @var ConfirmOrderRequestFactory */
    protected $confirmOrderRequestFactory;

    public function __construct(
        EshopSession $eshopSession,
        OrderRepository $orderRepository,
        ServiceFactory $serviceFactory = null,
        PatchRequestFactory $patchRequestFactory = null,
        OrderRequestFactory $orderRequestFactory = null,
        ConfirmOrderRequestFactory $confirmOrderRequestFactory = null
    )
    {
        $this->eshopSession = $eshopSession;
        $this->orderRepository = $orderRepository;
        $this->serviceFactory = $serviceFactory ?: Registry::get(ServiceFactory::class);
        $this->patchRequestFactory = $patchRequestFactory ?: Registry::get(PatchRequestFactory::class);
        $this->orderRequestFactory = $orderRequestFactory ?: Registry::get(OrderRequestFactory::class);
        $this->confirmOrderRequestFactory = $confirmOrderRequestFactory ?: Registry::get(ConfirmOrderRequestFactory::class);
    }

    public function doCreatePayPalOrder(EshopModelBasket $basket, $intent, $userAction = null): ApiModelOrder
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->orderRequestFactory->getRequest(
            $basket,
            $intent,
            $userAction
        );

        $response = [];
        try {
            $response = $orderService->createOrder($request, '', '');
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order create call.", [$exception]);
        }

        return $response;
    }

    public function doCreateAcdcOrder(EshopModelBasket $basket): array
    {
        $response = $this->doCreatePayPalOrder(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE
        );

        $paypalOrderId = $response->id ?: '';
        $status = $response->status ?: '';
        $this->doPatchPayPalOrder($basket, $paypalOrderId);

        $return = [
            'id' => $paypalOrderId,
            'status' => $status
        ];

        return $return;
    }

    public function doPatchPayPalOrder(EshopModelBasket $basket, string $checkoutOrderId, string $shopOrderId = ''): void
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->patchRequestFactory
            ->getRequest($basket, $shopOrderId);

        // Update Order
        try {
            //TODO: method has no return value, how can we verify update success?
            $orderService->updateOrder($checkoutOrderId, $request);
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order patch call.", [$exception]);
        }
    }

    /**
     * Executes capture to PayPal
     */
    public function doCapturePayPalOrder(EshopModelOrder $order, string $checkoutOrderId): ApiOrderModel
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        // Capture Order
        try {
            /** @var PayPalOrderModel $paypalOrder */
            $paypalOrder = $this->getPayPalOrder($order->getId(), $checkoutOrderId);

            $request = new OrderCaptureRequest();
            /** @var ApiOrderModel */
            $result = $orderService->capturePaymentForOrder('', $checkoutOrderId, $request, '');

            $paypalOrder->setPaymentMethodId($this->getSessionPaymentId());
            $paypalOrder->setStatus((string) $result->status);
            $paypalOrder->save();
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
            throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
        }

        if (ApiOrderModel::STATUS_COMPLETED === $paypalOrder->getStatus()) {
            $order->markOrderPaid();
        }

        return $result;
    }

    public function doExecuteUAPM(EshopModelBasket $basket, string $checkoutOrderId, string $uapmName): string
    {
        $redirectLink = '';

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = Registry::get(ConfirmOrderRequestFactory::class);
        $request = $requestFactory->getRequest(
            $basket,
            $uapmName
        );

        // toDo: Clearing with Marcus. Optional. Verifies that the payment originates from a valid, user-consented device and application. Reduces fraud and decreases declines. Transactions that do not include a client metadata ID are not eligible for PayPal Seller Protection.
        $payPalClientMetadataId = '';

        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $response = $orderService->confirmTheOrder(
            $payPalClientMetadataId,
            $checkoutOrderId,
            $request
        );

        if (!isset($response->links)) {
            throw PayPalException::uAPMPaymentMalformedResponse();
        }
        foreach ($response->links as $links) {
            if ($links['rel'] === 'payer-action') {
                $redirectLink = $links['href'];
            }
        }
        if (!$redirectLink) {
            throw PayPalException::uAPMPaymentMissingRedirectLink();
        }

        return $redirectLink;
    }

    /**
     * Return the PaymentId from session basket
     */
    public function getSessionPaymentId(): ?string
    {
        return $this->eshopSession->getBasket() ? $this->eshopSession->getBasket()->getPaymentId() : null;
    }

    public function getPayPalOrder(string $shopOrderId, string $payPalOrderId): PayPalOrderModel
    {
        return $this->orderRepository->paypalOrderByOrderIdAndPayPalId($shopOrderId, $payPalOrderId);
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

        if ( $payPalOrderId = PayPalSession::getUapmCheckoutOrderId()) {
            $payPalOrder = $this->orderRepository->paypalOrderByOrderIdAndPayPalId($sessionOrderId, $payPalOrderId);
            $payPalOrder->delete();
        }

        PayPalSession::unsetPayPalOrderId();
        PayPalSession::unsetPayPalOrderId(Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
        PayPalSession::unsetPayPalOrderId(Constants::SESSION_ACDCCHECKOUT_ORDER_ID);
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
        $payPalOrder = $this->getPayPalOrder($order->getId(), $uapmOrderId);
        $payPalOrder->setPaymentMethodId($basket->getPaymentId());
        PayPalSession::storePayPalOrderId($uapmOrderId, Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
        $redirectLink = '';

        try {
            $redirectLink = $this->doExecuteUAPM(
                $basket,
                $uapmOrderId,
                PayPalDefinitions::getPaymentSourceRequestName($basket->getPaymentId())
            );
            $payPalOrder->save();

        } catch (Exception $exception) {
            PayPalSession::unsetPayPalOrderId(Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
            $this->removeTemporaryOrder();
            //TODO: do we need to log this?
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        //NOTE: payment not fully executed, we need customer interaction first
        return $redirectLink;
    }

    public function doCreateUAPMOrder(EshopModelBasket $basket): string
    {
        $response = $this->doCreatePayPalOrder(
                $basket,
                OrderRequest::INTENT_CAPTURE
            );

        return $response->id ?: '';
    }
}
