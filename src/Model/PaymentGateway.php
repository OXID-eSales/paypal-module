<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use Exception;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Core\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
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
        $session = $this->getSession();

        if ($session->getVariable('isSubscriptionCheckout')) {
            $this->getSession()->deleteVariable('isSubscriptionCheckout');
            return true;
        }

        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        if (PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID == $paymentService->getSessionPaymentId()) {
            $success = $this->doExecutePayPalPayment($order);
        } elseif (PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID == $paymentService->getSessionPaymentId()) {
            $success = $this->doExecuteAcdcPayPalPayment($order);
        } else {
            $success = parent::executePayment($amount, $order);
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

    protected function doExecutePayPalPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $success = false;

        if ($checkoutOrderId = PayPalSession::getcheckoutOrderId()) {

            // Update Order
            try {
                $paymentService->doPatchPayPalOrder(Registry::getSession()->getBasket(), $checkoutOrderId);
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order patch call.", [$exception]);
            }

            // Capture Order
            try {
                $paymentService->doCapturePayPalOrder($order, $checkoutOrderId);
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

    protected function doExecuteAcdcPayPalPayment(EshopModelOrder $order): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $success = false;

        if ($checkoutOrderId = PayPalSession::getAcdcCheckoutOrderId()) {
            // Capture Order
            try {
                $paymentService->doCapturePayPalOrder($order, $checkoutOrderId);
                $success = true;
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on acdc order capture call.", [$exception]);
            }

            // destroy PayPal-Session
            PayPalSession::unsetPayPalOrderId();
            PayPalSession::unsetPayPalOrderId(Constants::SESSION_UAPMCHECKOUT_ORDER_ID);
            PayPalSession::unsetPayPalOrderId(Constants::SESSION_ACDCCHECKOUT_ORDER_ID);
        }

        return $success;
    }
}
