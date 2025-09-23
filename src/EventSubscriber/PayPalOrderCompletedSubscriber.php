<?php

namespace OxidSolutionCatalysts\PayPal\EventSubscriber;

use OxidSolutionCatalysts\PayPal\Event\PayPalOrderCompletedEvent;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PayPalOrderCompletedSubscriber implements EventSubscriberInterface
{
    /** @var PaymentService */
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

    public function onOrderCompleted(PayPalOrderCompletedEvent $event): void
    {
        $order = $event->getOrder();
        $basket = $event->getBasket();
        $user = $event->getUser();

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
    }
}
