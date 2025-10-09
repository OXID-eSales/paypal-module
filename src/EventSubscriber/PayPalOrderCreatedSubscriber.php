<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\EventSubscriber;

use OxidSolutionCatalysts\PayPal\Event\PayPalOrderCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

class PayPalOrderCreatedSubscriber implements EventSubscriberInterface
{

    /**
     * @var \OxidSolutionCatalysts\PayPal\Service\Payment
     */
    private $paymentService;

    public function __construct(
        PaymentService $paymentService
    )
    {
        $this->paymentService = $paymentService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PayPalOrderCreatedEvent::NAME => 'onOrderCreated',
        ];
    }

    public function onOrderCreated(PayPalOrderCreatedEvent $event): void
    {
        // track PayPal order
        $this->paymentService->trackPayPalOrder(
            $event->getShopOrderId(),
            $event->getPayPalOrderId(),
            $event->getPaymentsId(),
            PayPalApiOrder::STATUS_CREATED,
            $event->getTransactionId()
        );

        PayPalSession::unsetPayPalSession();
    }
}
