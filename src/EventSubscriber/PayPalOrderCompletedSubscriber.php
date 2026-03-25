<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\EventSubscriber;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Event\PayPalOrderCompletedEvent;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\NormalizedEventDispatcher;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Event\PayPalVaultingSucceededEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PayPalOrderCompletedSubscriber implements EventSubscriberInterface
{
    use NormalizedEventDispatcher;

    /**
     * @var PaymentService
     */
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PayPalOrderCompletedEvent::NAME => 'onOrderCompleted',
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function onOrderCompleted(PayPalOrderCompletedEvent $event): void
    {
        $order = $event->getOrder();
        $basket = $event->getBasket();
        $user = $event->getUser();

        // Reject tracking if shopOrderId is empty – this prevents orphaned
        // oscpaypal_order rows when sess_challenge was cleared before capture.
        if (empty($event->getShopOrderId())) {
            Registry::getLogger()->error(
                'PayPalOrderCompletedSubscriber: shopOrderId is empty, aborting order tracking',
                ['payPalOrderId' => $event->getPayPalOrderId()]
            );
            return;
        }

        // mark as paid and set transaction id
        if (method_exists($order, 'markOrderPaid')) {
            $order->markOrderPaid();
        }
        if (method_exists($order, 'setTransId')) {
            $order->setTransId($event->getTransactionId());
        }

        // track PayPal order
        $this->paymentService->trackPayPalOrder(
            $event->getShopOrderId(),
            $event->getPayPalOrderId(),
            $event->getPaymentsId(),
            PayPalApiOrder::STATUS_COMPLETED,
            $event->getTransactionId()
        );

        // send mail
        if ($basket && $user) {
            if (method_exists($order, 'sendPayPalOrderByEmail')) {
                $order->sendPayPalOrderByEmail($user, $basket);
            }
        }

        // cleanup session
        PayPalSession::unsetPayPalSession();

        $customerId = $event->getPayPalCustomerId();
        if (!empty($customerId)) {
            $vaultEvent = new PayPalVaultingSucceededEvent($user, $customerId);
            $this->dispatchNormalized($vaultEvent, PayPalVaultingSucceededEvent::NAME);
        }
    }
}
