<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\EventSubscriber;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
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
     * @var \OxidSolutionCatalysts\PayPal\Service\Payment
     */
    private $paymentService;
    /**
     * @var NormalizedEventDispatcher
     */

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
        $session = Registry::getSession();

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
        if (empty($customerId)) {
            $serviceFactory = Registry::get(ServiceFactory::class);
            $orderService = $serviceFactory->getOrderService();
            $payPalOrder = $orderService->showOrderDetails(
                $event->getPayPalOrderId(),
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );

            if($payPalOrder){
                if ($paypal = $payPalOrder->payment_source->paypal) {
                    $vault = $paypal->attributes->vault;
                } elseif ($card = $payPalOrder->payment_source->card) {
                    $vault = $card->attributes->vault;
                }

                if (isset($vault->customer_id) && !empty($vault->customer_id)) {
                    $customerId = $vault->customer_id;
                } else {
                    if (!empty($vault->status) && $vault->status === 'APPROVED') {
                         $session->deleteVariable("vaultSuccess");
                         $session->setVariable("vaultApproved", true);
                    }
                }
            }
        }

        if (!empty($customerId)) {
            $vaultEvent = new PayPalVaultingSucceededEvent($user, $customerId);
            $session->deleteVariable("vaultApproved");
            $this->dispatchNormalized( $vaultEvent, PayPalVaultingSucceededEvent::NAME);
        }
    }
}
