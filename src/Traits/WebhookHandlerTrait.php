<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

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

    public function getOrder(Event $event): EshopModelOrder
    {
        $payPalOrderId = $this->getPayPalOrderId($event);

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

    public function getPayPalOrderId(Event $event): string
    {
        $data = $this->getEventPayload($event);

        if (!isset($data['resource'])) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        $payPalOrderId = (string) $data['resource']['id'];

        if (!$payPalOrderId) {
            throw WebhookEventException::mandatoryDataNotFound();
        }

        return $payPalOrderId;
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

    public function setStatus(EshopModelOrder $order, string $status, string $payPalOrderId)
    {
        /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
        $paypalOrderModel = $this->getServiceFromContainer(OrderRepository::class)
            ->paypalOrderByOrderIdAndPayPalId($order->getId(), $payPalOrderId);

        $orderDetails = $this->serviceFactory
            ->getOrderService()
            ->showOrderDetails($payPalOrderId);

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
}
