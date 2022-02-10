<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use Exception;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Exception\PayPalException;
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


class Payment
{
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
        OrderRepository $orderRepository,
        ServiceFactory $serviceFactory = null,
        PatchRequestFactory $patchRequestFactory = null,
        OrderRequestFactory $orderRequestFactory = null,
        ConfirmOrderRequestFactory $confirmOrderRequestFactory = null
    )
    {
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

    public function doPatchPayPalOrder(EshopModelBasket $basket, string $checkoutOrderId): void
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        $request = $this->patchRequestFactory
            ->getRequest($basket);

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
    public function doCapturePayPalOrder(EshopModelOrder $order, string $checkoutOrderId): void
    {
        /** @var ApiOrderService $orderService */
        $orderService = $this->serviceFactory->getOrderService();

        // Capture Order
        try {
            /** @var PayPalOrderModel $paypalOrder */
            $paypalOrder = $this->orderRepository->paypalOrderByShopAndPayPalId($order->getId(), $checkoutOrderId);

            $request = new OrderCaptureRequest();
            /** @var ApiOrderModel */
            $result = $orderService->capturePaymentForOrder('', $checkoutOrderId, $request, '');

            $paypalOrder->setStatus((string) $result->status);
            $paypalOrder->save();
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
            throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
        }

        if (ApiOrderModel::STATUS_COMPLETED === $paypalOrder->getStatus()) {
            $order->markOrderPaid();
        }
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

        if ($response->links) {
            foreach ($response->links as $links) {
                if ($links->rel === 'payer_action') {
                    $redirectLink = $links->href;
                }
            }
        } else {
            throw PayPalException::uAPMPaymentFail();
        }

        return $redirectLink;
    }
}
