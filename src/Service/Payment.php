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
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
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

    public function doCreatePayPalOrder(
        EshopModelBasket $basket,
        string $intent,
        string $userAction = null,
        string $processingInstruction = null,
        string $paymentSource = '',
        string $payPalClientMetadataId = '',
        string $payPalRequestId = '',
        string $payPalPartnerAttributionId = ''
    #): ?ApiModelOrder
    ) //TODO return value
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->orderRequestFactory->getRequest(
            $basket,
            $intent,
            $userAction,
            null,
            $processingInstruction,
            $paymentSource
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
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order create call.", [$exception]);
        }

        return $response;
    }

    public function doCreatePatchedOrder(
        EshopModelBasket $basket
    ): array
    {
        // PatchOrders access an OrderCall that has taken place before.
        // For this reason, the payPalPartnerAttributionId does not have
        // to be transmitted again in the case of a PatchCall
        $response = $this->doCreatePayPalOrder(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE
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

    public function doPatchPayPalOrder(EshopModelBasket $basket, string $checkoutOrderId, string $paymentSource = '', string $shopOrderId = ''): void
    {
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

    public function doCapturePayPalOrder(EshopModelOrder $order, string $checkoutOrderId): ApiOrderModel
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        // Capture Order
        try {
            $request = new OrderCaptureRequest();
            /** @var ApiOrderModel */
            $result = $orderService->capturePaymentForOrder('', $checkoutOrderId, $request, '');

            /** @var PayPalOrderModel $paypalOrder */
            $payPalOrder = $this->trackPayPalOrder(
                $order->getId(),
                $checkoutOrderId,
                $this->getSessionPaymentId(),
                (string) $result->status
            );
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
            throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
        }

        if (ApiOrderModel::STATUS_COMPLETED === $payPalOrder->getStatus()) {
            $order->markOrderPaid();
        }

        return $result;
    }

    public function doConfirmUAPM(EshopModelOrder $order, EshopModelBasket $basket, string $checkoutOrderId, string $uapmName): string
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

        /** @var ApiModelOrder $response */
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

        PayPalSession::storePayPalOrderId($uapmOrderId, Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
        $redirectLink = '';

        try {
            $redirectLink = $this->doConfirmUAPM(
                $order,
                $basket,
                $uapmOrderId,
                PayPalDefinitions::getPaymentSourceRequestName($basket->getPaymentId())
            );

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
            OrderRequest::INTENT_CAPTURE,
            null,
            '',
            '',
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
        );

        return $response->id ?: '';
    }

    public function doExecutePuiPayment(EshopModelOrder $order, EshopModelBasket $basket, string $payPalClientMetadataId = ''): bool
    {
        try {
            $result = $this->doCreatePayPalOrder(
                $basket,
                OrderRequest::INTENT_CAPTURE,
                OrderRequestFactory::USER_ACTION_CONTINUE,
                Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
                PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME,
                $payPalClientMetadataId,
                $order->getId(),
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
            $payPalOrderId = $result['id'];
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on pui order creation call.", [$exception]);
        }

       # TODO: check what we created, ensure it is a pui order
       # $paymentSource = $this->fetchOrderFields((string) $payPalOrderId, 'payment_source');
       # Registry::getLogger()->error(serialize($paymentSource));

        if (!$payPalOrderId ) {
            return false;
        }

        $this->trackPayPalOrder(
            (string) $order->getId(),
            $payPalOrderId,
            $basket->getPaymentId(),
            $result['status']
        );

        $order->savePuiInvoiceNr($payPalOrderId);

        return (bool) $payPalOrderId;
    }

    public function trackPayPalOrder(
        string $shopOrderId,
        string $payPalOrderId,
        string $paymentMethodId,
        string $status
    ): PayPalOrderModel
    {
        /** @var PayPalOrderModel $payPalOrder */
        $payPalOrder = $this->orderRepository
            ->paypalOrderByOrderIdAndPayPalId($shopOrderId, $payPalOrderId);

        $payPalOrder->setPaymentMethodId($paymentMethodId);
        $payPalOrder->setStatus($status);
        $payPalOrder->save();

        return $payPalOrder;
    }

    public function fetchOrderFields(string $paypalOrderId, string $fields = ''): ApiOrderModel
    {
        return $this->serviceFactory
            ->getOrderService()
            ->showOrderDetails($paypalOrderId, $fields);
    }

}
