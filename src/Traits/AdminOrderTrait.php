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
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;

trait AdminOrderTrait
{
    use ServiceContainer;

    protected ?Order $order = null;

    protected ?bool $isPayPalStandardManuallyCapture = null;

    protected ?bool $isPayPalStandardOnDeliveryCapture = null;

    protected ?bool $isPayPalStandardOrder = null;

    protected ?bool $isAuthorizedPayPalStandardOrder = null;

    /**
     * @throws StandardException
     */
    public function paidWithPayPal(): bool
    {
        $order = $this->getOrder();
        if (method_exists($order, 'paidWithPayPal')) {
            $order->paidWithPayPal();
        }

        return false;
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
                (string)$orderId,
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                $paypalOrder
            );

            if (method_exists($order, 'isPayPalOrderCompleted') && $order->isPayPalOrderCompleted($result)) {
                /** @phpstan-ignore-next-line */
                $order->setTransId($result->purchase_units[0]->payments->captures[0]->id);
                /** @phpstan-ignore-next-line */
                $order->markOrderPaid();
            }
        } else {
            Registry::getUtilsView()->addErrorToDisplay('OSC_PAYPAL_CAPTURE_NOT_POSSIBLE_ANYMORE');
        }

        // reset the order to get new information about successful capture
        $this->refreshOrder();
    }

    /**
     * Template getter is it an Authorized PayPalStandardOrder
     */
    public function isAuthorizedPayPalStandardOrder(): bool
    {
        if (is_null($this->isAuthorizedPayPalStandardOrder)) {
            $this->isAuthorizedPayPalStandardOrder = (
                $this->isPayPalStandardOrder() &&
                $this->getPayPalCheckoutOrder()->intent === Constants::PAYPAL_ORDER_INTENT_AUTHORIZE
            );
        }
        return $this->isAuthorizedPayPalStandardOrder;
    }

    public function isPayPalStandardOnDeliveryCapture(): bool
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

    public function isPayPalStandardManuallyCapture(): bool
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
    public function getTimeLeftForPayPalCapture(bool $roundAsDay = true): float
    {
        $result = 0;
        if ($this->isAuthorizedPayPalStandardOrder()) {
            $result = time()
                - strtotime((string)$this->getPayPalCheckoutOrder()->create_time)
                + Constants::PAYPAL_MAXIMUM_TIME_FOR_CAPTURE;

            $result = $roundAsDay ? ceil($result / Constants::PAYPAL_DAY) : $result;
        }
        return $result;
    }

    /**
     * @throws StandardException
     */
    protected function isPayPalStandardOrder(): ?bool
    {
        if (is_null($this->isPayPalStandardOrder)) {
            $this->isPayPalStandardOrder = false;
            $order = $this->getOrder();
            if (
                property_exists($order, 'oxorder__oxpaymenttype')
                && $order->oxorder__oxpaymenttype->value === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID
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
        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();
        return $order->getPayPalCheckoutOrder();
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
            if ($orderId == null || !$order->load($orderId)) {
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
