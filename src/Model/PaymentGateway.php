<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

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
     * @param double          $amount Goods amount
     * @param EshopModelOrder $order  User ordering object
     *
     */
    public function executePayment($amount, &$order)
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = $paymentService->getSessionPaymentId();

        if (PayPalDefinitions::isButtonPayment($sessionPaymentId)) {
            $success = $this->doExecutePayPalExpressPayment($order);
        } elseif (PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID === $sessionPaymentId) {
            $success = $this->doExecutePuiPayment($order);
        } else {
            $success = parent::executePayment($amount, $order);
        }
        $paypalOrderId = '';
        if (
            $sessionPaymentId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID ||
            $sessionPaymentId === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID
        ) {
            $paypalOrderId = Registry::getRequest()->getRequestParameter('orderID');
        }
        if (
            $success &&
            $paymentService->isPayPalPayment()
        ) {
            $capture = $order->getOrderPaymentCapture($paypalOrderId);
            if ($capture && (string) $capture->status === 'COMPLETED') {
                $order->setTransId($capture->id);
                $order->markOrderPaid();
            }
        }

        return $success;
    }

    protected function doExecutePayPalExpressPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = (string) $paymentService->getSessionPaymentId();
        $success = false;

        /** @var Logger $logger */
        $logger = $this->getServiceFromContainer(Logger::class);

        if ($checkoutOrderId = PayPalSession::getCheckoutOrderId()) {
            // Update Order
            try {
                $paymentService->doPatchPayPalOrder(Registry::getSession()->getBasket(), $checkoutOrderId);
            } catch (Exception $exception) {
                $logger->log('error', 'Error on order patch call.', [$exception]);
            }

            // Capture Order
            try {
                // At this point we only trigger the capture. We find out that order was really captured via the
                // CHECKOUT.ORDER.COMPLETED webhook, where we mark the order as paid
                $paymentService->doCapturePayPalOrder($order, $checkoutOrderId, $sessionPaymentId);
                // success means at this point, that we triggered the capture without errors
                $success = true;
            } catch (Exception $exception) {
                $logger->log('error', 'Error on order capture call.', [$exception]);
            }

            // destroy PayPal-Session
            PayPalSession::unsetPayPalOrderId();
        }

        return $success;
    }

    protected function doExecutePuiPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $success = false;
        try {
            //order number must be resolved before requesting payment
            $order->setOrderNumber();
            $success = $paymentService->doExecutePuiPayment(
                $order,
                Registry::getSession()->getBasket(),
                PayPalSession::getPayPalPuiCmId()
            );
            PayPalSession::unsetPayPalPuiCmId();
        } catch (Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log('error', 'Error on execute pui payment call.', [$exception]);
        }
        // destroy PayPal-Session
        PayPalSession::unsetPayPalOrderId();

        $this->_sLastError = $paymentService->getPaymentExecutionError();

        return $success;
    }
}
