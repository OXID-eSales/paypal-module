<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Patch;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;


/**
 * Class PaymentGateway
 * @package OxidSolutionCatalysts\PayPal\Model
 *
 * @mixin \OxidEsales\Eshop\Application\Model\PaymentGateway
 */
class PaymentGateway extends PaymentGateway_parent
{
     use ServiceContainer;

     /**
     * Executes payment, returns true on success.
     *
     * @param double                               $amount Goods amount
     * @param \OxidEsales\PayPalModule\Model\Order $order  User ordering object
     *
     * @return bool
     */
    public function executePayment($amount, &$order)
    {
        $success = parent::executePayment($amount, $order);
        $session = $this->getSession();

        if ($session->getVariable('isSubscriptionCheckout')) {
            $this->getSession()->deleteVariable('isSubscriptionCheckout');
            return true;
        }

        if ($this->getSessionPaymentId() === 'oxidpaypal') {
            $success = $this->doAuthorizePayPalPayment($order);
        }

        return $success;
    }

     /**
     * Return the PaymentId from Session
     */
    public function getSessionPaymentId()
    {
        $session = $this->getSession();
        return $session->getVariable('paymentid') ?? $session->getBasket()->getPaymentId();
    }

    /**
     * Executes Authorize to PayPal
     *
     * @param Order $order  User ordering object
     *
     * @return bool
     */
    protected function doAuthorizePayPalPayment(&$order)
    {
        $success = false;

        try {
            // updating order state
            if ($order) {
                if ($checkoutOrderId = PayPalSession::getcheckoutOrderId()) {

                    //TODO: refactor this method

                    /** @var ServiceFactory $serviceFactory */
                    $serviceFactory = Registry::get(ServiceFactory::class);
                    $service = $serviceFactory->getOrderService();

                    /** @var PatchRequestFactory $requestFactory */
                    $requestFactory = Registry::get(PatchRequestFactory::class);
                    $request = $requestFactory->getRequest(
                        Registry::getSession()->getBasket()
                    );

                    // Update Order
                    try {
                        $response = $service->updateOrder($checkoutOrderId, $request);
                    } catch (Exception $exception) {
                        Registry::getLogger()->error("Error on order patch call.", [$exception]);
                    }

                    $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
                    /** @var PayPalOrderModel $paypalOrder */
                    $paypalOrder = $orderRepository->paypalOrderByShopAndPayPalId($order->getId(), $checkoutOrderId);

                    // Capture Order
                    try {
                        $request = new OrderCaptureRequest();
                        /** @var ApiOrderModel $result */
                        $result = $service->capturePaymentForOrder('', $checkoutOrderId, $request, '');
                        $paypalOrder->setStatus((string) $result->status);
                        $paypalOrder->save();
                    } catch (Exception $exception) {
                        Registry::getLogger()->error("Error on order capture call.", [$exception]);
                        throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
                    }

                    if (ApiOrderModel::STATUS_COMPLETED === $paypalOrder->getStatus()) {
                        $order->markOrderPaid();
                    }

                    $success = true;
                }
            } else {
                $exception = oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
                throw $exception;
            }
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on doAuthorizePayPalPayment call.", [$exception]);

            Registry::getUtilsView()->addErrorToDisplay($exception);
        }

        // destroy PayPal-Session
        PayPalSession::storePayPalOrderId('');

        return $success;
    }
}
