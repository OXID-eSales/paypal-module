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
            $success = $this->doExecutePayPalPayment($order);
        } elseif (PayPalDefinitions::isUAPMPayment($this->getSessionPaymentId())) {
            $this->doExecuteUAPMPayment();
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
        /** @var CorePaymentService $paymentService */
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
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order capture call.", [$exception]);
                throw oxNew(StandardException::class, 'OXPS_PAYPAL_ORDEREXECUTION_ERROR');
            }

            $success = true;

            // destroy PayPal-Session
            PayPalSession::storePayPalOrderId('');
        }

        return $success;
    }

    protected function doCreateUAPMOrder(): bool
    {
        $success = false;

        $response = $this->getServiceFromContainer(PaymentService::class)
            ->doCreatePayPalOrder(
                Registry::getSession()->getBasket(),
                OrderRequest::INTENT_CAPTURE
            );

        if ($response->id) {
            $success = true;
            $checkoutOrderId = $response->id;
            PayPalSession::storePayPalOrderId($checkoutOrderId);
        }

        return $success;
    }

    /**
     * @throws PayPalException
     */
    protected function doExecuteUAPMPayment(): void
    {
        //For UAPM payment we should not yet have a paypal order in session.
        //We create a fresh paypal order at this point
        if (!$this->doCreateUAPMOrder()) {
            throw PayPalException::createPayPalOrderFail();
        }
        try {
            $redirectLink = $this->getServiceFromContainer(PaymentService::class)->doExecuteUAPM(
                Registry::getSession()->getBasket(),
                PayPalSession::getcheckoutOrderId(),
                PayPalDefinitions::getPaymentSourceRequestName($this->getSessionPaymentId())
            );
        } catch (Exception $exception) {
            $redirectLink = Registry::getConfig()->getSslShopUrl() . 'index.php?cl=payment';
            //TODO: do we need to log this?
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        throw new Redirect($redirectLink);
    }
}
