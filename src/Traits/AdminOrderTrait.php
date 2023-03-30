<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

trait AdminOrderTrait
{
    use ServiceContainer;

    /** @var Order|null */
    protected $order = null;

    /** @var bool|null  */
    protected $isPayPalStandardManuallyCapture = null;

    /** @var bool|null  */
    protected $isPayPalStandardOnDeliveryCapture = null;

    /** @var bool|null  */
    protected $isPayPalStandardOrder = null;

    /** @var bool|null  */
    protected $isAuthorizedPayPalStandardOrder = null;

    /**
     * @throws StandardException
     */
    public function paidWithPayPal(): bool
    {
        return (bool)$this->getOrder()->paidWithPayPal();
    }

    /**
     * Capture payment action
     *
     * @throws ApiException
     * @throws StandardException
     */
    public function capturePayPalStandard(): void
    {
        if (
            $this->getTimeLeftForPayPalCapture(false) > 0
        ) {
            $order = $this->getOrder();
            $paypalOrder = $this->getPayPalCheckoutOrder();
            $orderId = $paypalOrder->id;

            $service = $this->getServiceFromContainer(PaymentService::class);
            /** @var PayPalOrder $result */
            $result = $service->doCapturePayPalOrder(
                $order,
                $orderId,
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                $paypalOrder
            );
            if ($order->isPayPalOrderCompleted($result)) {
                $order->setTransId($result->purchase_units[0]->payments->captures[0]->id);
                $order->markOrderPaid();
            }
        } else {
            Registry::getUtilsView()->addErrorToDisplay('OSC_PAYPAL_CAPTURE_NOT_POSSIBLE_ANYMORE');
        }

        // reset the order to get new informations about successful capture
        $this->refreshOrder();
    }

    /**
     * Template getter is it a Authorized PayPalStandardOrder
     */
    public function isAuthorizedPayPalStandardOrder()
    {
        if (is_null($this->isAuthorizedPayPalStandardOrder)) {
            $this->isAuthorizedPayPalStandardOrder = (
                $this->isPayPalStandardOrder() &&
                $this->getPayPalCheckoutOrder()->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE
            );
        }
        return $this->isAuthorizedPayPalStandardOrder;
    }

    public function isPayPalStandardOnDeliveryCapture()
    {
        if (is_null($this->isPayPalStandardOnDeliveryCapture)) {
            $this->isPayPalStandardOnDeliveryCapture = false;
            if ($this->isAuthorizedPayPalStandardOrder()) {
                $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
                if (
                    $moduleSettings->getPayPalStandardCaptureStrategy() === 'delivery'
                ) {
                    $this->isPayPalStandardOnDeliveryCapture = true;
                }
            }
        }
        return $this->isPayPalStandardOnDeliveryCapture;
    }

    public function isPayPalStandardManuallyCapture()
    {
        if (is_null($this->isPayPalStandardManuallyCapture)) {
            $this->isPayPalStandardManuallyCapture = false;
            $paypalOrder = $this->getPayPalCheckoutOrder();
            if ($this->isAuthorizedPayPalStandardOrder()) {
                $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
                if (
                    $moduleSettings->getPayPalStandardCaptureStrategy() === 'manually'
                ) {
                    $this->isPayPalStandardManuallyCapture = true;
                }
            }
        }
        return $this->isPayPalStandardManuallyCapture;
    }

    /**
     * Template getter How many Days left for capture?
     */
    public function getTimeLeftForPayPalCapture($roundAsDay = true)
    {
        $result = 0;
        if ($this->isAuthorizedPayPalStandardOrder()) {
            $result = time()
                - strtotime($this->getPayPalCheckoutOrder()->create_time)
                + Constants::PAYPAL_MAXIMUM_TIME_FOR_CAPTURE;

            $result = $roundAsDay ? ceil($result / Constants::PAYPAL_DAY) : $result;
        }
        return $result;
    }

    protected function isPayPalStandardOrder()
    {
        if (is_null($this->isPayPalStandardOrder)) {
            $this->isPayPalStandardOrder = false;
            if (
                ($order = $this->getOrder()) &&
                ($order->oxorder__oxpaymenttype->value === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID)
            ) {
                $this->isPayPalStandardOrder = true;
            }
        }
        return $this->isPayPalStandardOrder;
    }

    /**
     * @return PayPalOrder
     * @throws StandardException
     * @throws ApiException
     */
    protected function getPayPalCheckoutOrder(): PayPalOrder
    {
        $order = $this->getOrder();
        /** @var PayPalOrder $payPalOrder */
        $payPalOrder = $order->getPayPalCheckoutOrder();
        return $payPalOrder;
    }

    /**
     * Get active order
     *
     * @return Order
     * @throws StandardException
     */
    protected function getOrder(): Order
    {
        if (is_null($this->order)) {
            $order = oxNew(Order::class);
            $orderId = $this->getEditObjectId();
            if ($orderId === null || !$order->load($orderId)) {
                throw new StandardException('PayPalCheckout-Order not found');
            }
            $this->order = $order;
        }
        return $this->order;
    }

    protected function refreshOrder(): void
    {
        $this->order = null;
    }
}
