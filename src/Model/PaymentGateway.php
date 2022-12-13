<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use Exception;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;

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

        if (PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID == $sessionPaymentId) {
            $success = $this->doExecutePayPalExpressPayment($order);
        } elseif (PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID == $sessionPaymentId) {
            $success = $this->doExecuteAcdcPayPalPayment($order);
        } elseif (PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID == $sessionPaymentId) {
            $success = $this->doExecutePuiPayment($order);
        } else {
            $success = parent::executePayment($amount, $order);
        }
        if (
            $success &&
            $paymentService->isPayPalPayment() &&
            ($capture = $order->getOrderPaymentCapture()) &&
            (string) $capture->status === 'COMPLETED'
        ) {
            $order->setTransId($capture->id);
            $order->markOrderPaid();
        }

        return $success;
    }

    protected function doExecutePayPalExpressPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = (string) $paymentService->getSessionPaymentId();
        $success = false;

        if ($checkoutOrderId = PayPalSession::getCheckoutOrderId()) {
            // Update Order
            try {
                $paymentService->doPatchPayPalOrder(Registry::getSession()->getBasket(), $checkoutOrderId);
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order patch call.", [$exception]);
            }

            // Capture Order
            try {
                $paymentService->doCapturePayPalOrder($order, $checkoutOrderId, $sessionPaymentId);
                $success = true;
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order capture call.", [$exception]);
                $success = false;
            }

            // destroy PayPal-Session
            PayPalSession::storePayPalOrderId('');
        }

        return $success;
    }

    protected function doExecutePuiPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $success = false;
        try {
            $success = $paymentService->doExecutePuiPayment(
                $order,
                Registry::getSession()->getBasket(),
                PayPalSession::getPayPalPuiCmId()
            );
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on execute pui payment call.", [$exception]);
        }
        PayPalSession::unsetPayPalPuiCmId();

        $this->_sLastError = $paymentService->getPaymentExecutionError();

        return $success;
    }

    protected function doExecuteAcdcPayPalPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = (string) $paymentService->getSessionPaymentId();

        $success = false;

        if ($checkoutOrderId = PayPalSession::getCheckoutOrderId()) {
            // Capture Order
            try {
                $paymentService->doCapturePayPalOrder($order, $checkoutOrderId, $sessionPaymentId);
                $success = true;
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on acdc order capture call.", [$exception]);
            }

            // remove PayPal order id from session
            PayPalSession::unsetPayPalOrderId();
        }

        return $success;
    }
}
