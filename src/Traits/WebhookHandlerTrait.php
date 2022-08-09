<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as OrderResponse;

trait WebhookHandlerTrait
{
    use ServiceContainer;

    public function getOrderByOrderId(Event $event): EshopModelOrder
    {
        $payPalOrderId = $this->getPayPalId($event);

        //get PayPalOrder
        try {
            $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
            /** @var EshopModelOrder $order */
            $order = $orderRepository->getShopOrderByPayPalOrderId($payPalOrderId);
        } catch (NotFound $exception) {
            throw WebhookEventException::byPayPalOrderId($payPalOrderId);
        }

        return $order;
    }

    public function getOrderByTransactionId(Event $event): EshopModelOrder
    {
        $payPalTransactionId = $this->getPayPalId($event);

        //get PayPalOrder
        try {
            $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
            /** @var EshopModelOrder $order */
            $order = $orderRepository->getShopOrderByPayPalTransactionId($payPalTransactionId);
        } catch (NotFound $exception) {
            throw WebhookEventException::byPayPalTransactionId($payPalTransactionId);
        }

        return $order;
    }

    public function getPayPalId(Event $event): string
    {
        $data = $this->getEventPayload($event);

        if (!isset($data['resource'])) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        $payPalId = (string) $data['resource']['id'];

        if (!$payPalId) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        return $payPalId;
    }

    public function getEventPayload(Event $event): array
    {
        return $event->getData();
    }

    public function isCompleted($data): bool
    {
        return (
            isset($data['status']) &&
            isset($data['purchase_units'][0]['payments']['captures'][0]['status']) &&
            $data['status'] == OrderResponse::STATUS_COMPLETED &&
            $data['purchase_units'][0]['payments']['captures'][0]['status'] == Capture::STATUS_COMPLETED
        );
    }

    public function setStatus(EshopModelOrder $order, string $status, string $payPalOrderId = '', string $payPalTransactionId = '')
    {
        /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
        $orderService = $this->getServiceFromContainer(OrderRepository::class);

        if ($payPalTransactionId) {
            $paypalOrderModel = $orderService->paypalOrderByOrderIdAndPayPalId($order->getId(), $payPalOrderId, $payPalTransactionId);
            $payPalOrderId = $payPalOrderId ?: $paypalOrderModel->getPayPalOrderId();
        }
        else {
            $paypalOrderModel = $orderService->paypalOrderByOrderIdAndPayPalId($order->getId(), $payPalOrderId);
        }

        $orderDetails = Registry::get(ServiceFactory::class)
            ->getOrderService()
            ->showOrderDetails($payPalOrderId, '');

        if ($puiPaymentDetails = $orderDetails->payment_source->pay_upon_invoice ?? null) {
            $paypalOrderModel->setPuiPaymentReference($puiPaymentDetails->payment_reference);
            $paypalOrderModel->setPuiBic($puiPaymentDetails->bic);
            $paypalOrderModel->setPuiIban($puiPaymentDetails->iban);
            $paypalOrderModel->setPuiBankName($puiPaymentDetails->bank_name);
            $paypalOrderModel->setPuiAccountHolderName($puiPaymentDetails->account_holder_name);
        }

        $paypalOrderModel->setStatus($status);
        $paypalOrderModel->save();
    }

    public function cleanUpNotFinishedOrders() : void
    {
        // check for not finished orders and reset
        /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
        $this->getServiceFromContainer(OrderRepository::class)
            ->cleanUpNotFinishedOrders();
    }
}
